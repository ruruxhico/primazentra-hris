<?php

namespace App\Controllers;

use App\Libraries\BrevoMailer;
use App\Libraries\FirestoreService;
use Google\Cloud\Core\Timestamp;

class Auth extends BaseController {
    protected FirestoreService $firestore;

    public function __construct() {
        $this->firestore = FirestoreService::getInstance();
    }

    public function login() {
        $rememberedEmail = (string) $this->request->getCookie('remember_email');
        $rememberChecked = $rememberedEmail !== '' || $this->request->getCookie('remember_me') === '1';

        return view('auth/login', [
            'rememberedEmail' => $rememberedEmail,
            'rememberChecked' => $rememberChecked,
        ]);
    }

    public function forgotPassword() {
        return view('auth/forgot_password');
    }

    public function processForgotPassword() {
        $email = strtolower(trim((string) $this->request->getPost('email')));

        $validationData = [
            'email' => $email,
        ];

        if (! $this->validateData($validationData, 'authForgotPassword')) {
            $errors = $this->validator ? $this->validator->getErrors() : [];
            $firstError = is_array($errors) && $errors !== [] ? (string) reset($errors) : 'Please review the email address and try again.';

            return redirect()->back()->withInput()->with('error', $firstError);
        }

        try {
            $firebaseAuth = $this->firestore->getAuth();
            $firebaseAuth->getUserByEmail($email);

            $resetLink = $this->buildAppPasswordResetLink($email, $firebaseAuth);
            $this->sendAppPasswordResetEmail($email, $resetLink);
        } catch (\Throwable $e) {
            log_message('error', 'Password reset request failed for ' . $email . ': ' . $e->getMessage());

            if (str_contains(strtolower($e->getMessage()), 'user not found')) {
                return redirect()->back()->withInput()->with('error', 'No Firebase Auth account was found for that email address.');
            }

            return redirect()->back()->withInput()->with('error', 'Unable to send the reset link right now. Please try again later.');
        }

        return redirect()->to('/forgot-password')->with('success', 'If an account exists for that email, a password reset link has been sent.');
    }

    private function buildAppPasswordResetLink(string $email, $firebaseAuth): string
    {
        $actionCodeSettings = [
            'continueUrl' => rtrim(base_url('/reset-password'), '/'),
            'handleCodeInApp' => true,
        ];

        $firebaseLink = $firebaseAuth->getPasswordResetLink($email, $actionCodeSettings);
        $queryString = (string) parse_url($firebaseLink, PHP_URL_QUERY);
        parse_str($queryString, $query);

        $oobCode = (string) ($query['oobCode'] ?? '');

        if ($oobCode === '') {
            throw new \RuntimeException('Unable to generate password reset code.');
        }

        return site_url('reset-password?oobCode=' . rawurlencode($oobCode));
    }

    private function sendAppPasswordResetEmail(string $email, string $resetLink): void
    {
        $html = view('emails/password_reset', [
            'email' => $email,
            'resetLink' => $resetLink,
        ]);

        (new BrevoMailer())->send($email, 'PrimaZentra password reset', $html);
    }

    /**
     * Show reset form (linked from email). Expects ?oobCode=...
     */
    public function resetPassword() {
        $oobCode = (string) $this->request->getGet('oobCode');

        if ($oobCode === '') {
            return redirect()->to('/forgot-password')->with('error', 'Invalid or missing reset code.');
        }

        try {
            $email = $this->firestore->getAuth()->verifyPasswordResetCode($oobCode);
        } catch (\Throwable $e) {
            return redirect()->to('/forgot-password')->with('error', 'Invalid or expired reset link.');
        }

        return view('auth/reset_password', ['oobCode' => $oobCode, 'email' => $email]);
    }

    public function processResetPassword() {
        $oobCode = (string) $this->request->getPost('oobCode');
        $password = (string) $this->request->getPost('password');
        $confirm = (string) $this->request->getPost('confirm_password');

        $validationData = [
            'oobCode' => $oobCode,
            'password' => $password,
            'confirm_password' => $confirm,
        ];

        if (! $this->validateData($validationData, 'authResetPassword')) {
            $errors = $this->validator ? $this->validator->getErrors() : [];
            $firstError = is_array($errors) && $errors !== [] ? (string) reset($errors) : 'Please review the form.';
            return redirect()->back()->withInput()->with('error', $firstError);
        }

        try {
            $email = $this->firestore->getAuth()->confirmPasswordReset($oobCode, $password, true);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Unable to reset password: ' . $e->getMessage());
        }

        // Clear the temporary-password flags so the user is not bounced to /change-password on next login
        try {
            $fbUser = $this->firestore->getAuth()->getUserByEmail($email);
            $uid    = (string) $fbUser->uid;
            if ($uid !== '') {
                $this->firestore->getDb()->collection('users')->document($uid)->update([
                    ['path' => 'must_change_password',     'value' => false],
                    ['path' => 'temp_password_expires_at', 'value' => null],
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'processResetPassword: failed to clear must_change_password for ' . $email . ': ' . $e->getMessage());
        }

        return redirect()->to('/login')->with('success', 'Your password has been reset successfully. You may now sign in.');
    }

    public function processLogin() {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $rememberMe = (string) $this->request->getPost('remember_me') === '1';

        if ($email === '' || $password === '') {
            return redirect()->back()->withInput()->with('error', 'Email and password are required.');
        }

        try {
            $signInResult = $this->firestore->getAuth()->signInWithEmailAndPassword($email, $password);
            $firebaseUid = (string) ($signInResult->firebaseUserId() ?? '');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Invalid email or password.');
        }

        if ($firebaseUid === '') {
            return redirect()->back()->withInput()->with('error', 'Invalid email or password.');
        }

        if ($this->requiresEmailVerification()) {
            try {
                $firebaseUserRecord = $this->firestore->getAuth()->getUser($firebaseUid);
                if (! $firebaseUserRecord->emailVerified) {
                    return redirect()->back()->withInput()->with('error', 'Please verify your email address before signing in.');
                }
            } catch (\Throwable $e) {
                return redirect()->back()->withInput()->with('error', 'Unable to verify account status. Please contact support.');
            }
        }

        try {
            $userModel = new \App\Models\Model_Users();
            $user = $userModel->getUserById($firebaseUid) ?? $userModel->getUserByEmail($email);
        } catch (\Throwable $e) {
            log_message('error', 'Firestore user lookup failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Unable to load account profile. Please try again later.');
        }

        if (!$user || !is_array($user['data'] ?? null)) {
            return redirect()->back()->withInput()->with('error', 'Account profile not found. Please contact support.');
        }

        $userData = $user['data'];

        // Block unverified staff accounts
        if (isset($userData['is_verified']) && $userData['is_verified'] === false) {
            return redirect()->back()->withInput()->with('error', 'Please verify your account via the email sent to you before logging in.');
        }

        // Site Engineers can only access the system through the mobile app
        if (($userData['role'] ?? '') === 'Site Engineer') {
            return redirect()->back()->withInput()->with('error', 'Site Engineer accounts can only log in through the mobile app.');
        }

        // Check if this is a first-time login with a temporary password
        if (!empty($userData['must_change_password'])) {
            $expiresAt = $userData['temp_password_expires_at'] ?? null;
            if ($expiresAt !== null) {
                if (is_object($expiresAt) && method_exists($expiresAt, 'get')) {
                    $expiresTs = $expiresAt->get()->getTimestamp();
                } elseif (is_object($expiresAt) && method_exists($expiresAt, 'getTimestamp')) {
                    $expiresTs = $expiresAt->getTimestamp();
                } else {
                    $expiresTs = (int) $expiresAt;
                }

                if (time() > $expiresTs) {
                    return redirect()->back()->withInput()->with('error',
                        'Your temporary password has expired. <a href="' . base_url('forgot-password') . '" style="color:#7b1f1f;font-weight:700;text-decoration:underline;">Use Forgot Password</a> to set a new one, or contact your administrator.');
                }
            }

            // Valid temp password — log in with the force-change flag
            $sessionPic = self::resolveProfilePic($userData);
            session()->set([
                'isLoggedIn'              => true,
                'userId'                  => $user['id'],
                'userEmail'               => (string)($userData['email'] ?? $email),
                'userName'                => trim((string) ($userData['first_name'] ?? '') . ' ' . (string) ($userData['last_name'] ?? '')),
                'role'                    => $userData['role'],
                'login_time'              => time(),
                'remember_me'             => $rememberMe,
                'force_change_password'   => true,
                'temp_password_expires_at'=> $expiresTs ?? null,
                'profilePicture'          => $sessionPic,
            ]);

            return redirect()->to('/change-password');
        }

        $sessionPic = self::resolveProfilePic($userData);
        session()->set([
            'isLoggedIn'     => true,
            'userId'         => $user['id'],
            'userEmail'      => (string)($userData['email'] ?? $email),
            'userName'       => trim((string) ($userData['first_name'] ?? '') . ' ' . (string) ($userData['last_name'] ?? '')),
            'role'           => $userData['role'],
            'login_time'     => time(),
            'remember_me'    => $rememberMe,
            'profilePicture' => $sessionPic,
        ]);

        $this->applyRememberMeCookies($rememberMe, $email);

        return redirect()->to('/dashboard');
    }

    public function changePassword()
    {
        if (!session()->get('isLoggedIn') || !session()->get('force_change_password')) {
            return redirect()->to(session()->get('isLoggedIn') ? '/dashboard' : '/login');
        }

        return view('auth/change_password', [
            'tempPasswordExpiresAt' => session()->get('temp_password_expires_at'),
        ]);
    }

    public function processChangePassword()
    {
        if (!session()->get('isLoggedIn') || !session()->get('force_change_password')) {
            return redirect()->to('/login');
        }

        $newPassword     = (string) $this->request->getPost('password');
        $confirmPassword = (string) $this->request->getPost('confirm_password');

        if (strlen($newPassword) < 8) {
            return redirect()->back()->with('error', 'Password must be at least 8 characters long.');
        }

        if ($newPassword !== $confirmPassword) {
            return redirect()->back()->with('error', 'Passwords do not match.');
        }

        $uid = (string) session()->get('userId');

        try {
            $this->firestore->getAuth()->updateUser($uid, ['password' => $newPassword]);
        } catch (\Throwable $e) {
            log_message('error', 'Auth::processChangePassword - Firebase update failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update password. Please try again.');
        }

        try {
            $this->firestore->getDb()->collection('users')->document($uid)->set([
                'must_change_password'     => false,
                'temp_password_expires_at' => null,
            ], ['merge' => true]);
        } catch (\Throwable $e) {
            log_message('error', 'Auth::processChangePassword - Firestore update failed: ' . $e->getMessage());
        }

        session()->destroy();

        return redirect()->to('/login')->with('success', 'Password changed successfully. Please log in with your new password.');
    }

    // ── Invite token helpers ──────────────────────────────────────────────

    private function validateInviteToken(string $token): bool
    {
        if ($token === '') return false;
        try {
            $doc = $this->firestore->getDb()
                ->collection('invite_tokens')->document($token)->snapshot();
            if (!$doc->exists()) return false;
            $d = $doc->data();
            if ($d['used'] ?? false) return false;
            $exp = $d['expires_at'] ?? null;
            if ($exp instanceof Timestamp && $exp->get() < new \DateTime()) return false;
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function consumeInviteToken(string $token): void
    {
        try {
            $this->firestore->getDb()
                ->collection('invite_tokens')->document($token)
                ->update([['path' => 'used', 'value' => true]]);
        } catch (\Throwable $e) {
            log_message('error', 'Auth::consumeInviteToken - ' . $e->getMessage());
        }
    }

    public function generateInvite()
    {
        if (!session()->get('isLoggedIn') || !pz_can('create_staff')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
        }
        // This invite flow only ever creates Administrator accounts (see processRegister) —
        // block issuing new invites once one already exists.
        if ($this->adminAlreadyExists()) {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'An Administrator account already exists. Only one Administrator is allowed.']);
        }
        $token = bin2hex(random_bytes(32));
        try {
            $now = new Timestamp(new \DateTime());
            $exp = new Timestamp((new \DateTime())->modify('+48 hours'));
            $this->firestore->getDb()->collection('invite_tokens')->document($token)->set([
                'token'          => $token,
                'created_at'     => $now,
                'expires_at'     => $exp,
                'used'           => false,
                'created_by_uid' => (string)(session()->get('userId') ?? ''),
            ]);
            return $this->response->setJSON([
                'url' => base_url('register?token=' . $token),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Auth::generateInvite - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Could not generate invite link.']);
        }
    }

    // ── Registration ──────────────────────────────────────────────────────

    private function adminAlreadyExists(): bool
    {
        $admins = $this->firestore->getDb()->collection('users')
            ->where('role', '=', 'Administrator')
            ->documents();
        foreach ($admins as $doc) {
            if ($doc->exists()) {
                return true;
            }
        }
        return false;
    }

    public function register() {
        $token = (string)($this->request->getGet('token') ?? '');
        // A valid, unexpired invite token is required to even view this page.
        if (!$this->validateInviteToken($token)) {
            return redirect()->to('/login')->with('error', 'This registration link is invalid or has expired.');
        }
        return view('auth/register', ['invite_token' => $token]);
    }

    public function processRegister() {
        $inviteToken = (string)($this->request->getPost('invite_token') ?? '');
        if (!$this->validateInviteToken($inviteToken)) {
            return redirect()->to('/login')->with('error', 'This registration link is invalid or has expired.');
        }

        // Defense in depth: reject if an Administrator already exists, even if an
        // invite link was generated earlier (e.g. before this restriction shipped).
        if ($this->adminAlreadyExists()) {
            return redirect()->to('/login')->with('error', 'An Administrator account already exists. Only one Administrator is allowed.');
        }

        // Logic for Administrator self-registration
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $confirmPassword = (string) $this->request->getPost('confirm_password');

        $validationData = [
            'email' => $email,
            'password' => $password,
            'confirm_password' => $confirmPassword,
        ];

        if (! $this->validateData($validationData, 'authRegister')) {
            $errors = $this->validator ? $this->validator->getErrors() : [];
            $firstError = is_array($errors) && $errors !== [] ? (string) reset($errors) : 'Please review the registration form details.';

            return redirect()->back()->withInput()->with('error', $firstError);
        }

        $countryCode = strtoupper(trim((string) $this->request->getPost('country_code')));
        if ($countryCode === '') {
            $countryCode = 'PH';
        }

        $contactNumber = trim((string) $this->request->getPost('contact_number'));
        if ($contactNumber === '') {
            $contactNumber = $this->buildContactNumber(
                $countryCode,
                (string) $this->request->getPost('contact_number_display')
            );
        }

        $data = [
            'first_name'  => $this->request->getPost('firstName'),
            'last_name'   => $this->request->getPost('lastName'),
            'middle_name' => $this->request->getPost('middleName'),
            'email'       => $email,
            'country_code' => $countryCode,
            'contact_number' => $contactNumber,
            'role'        => 'Administrator',
            'created_at'  => new Timestamp(new \DateTime()),
            'must_change_password' => false
        ];

        try {
            $firebaseAuth = $this->firestore->getAuth();
            $firebaseUser = $firebaseAuth->createUserWithEmailAndPassword($data['email'], $password);
            $firebaseUid = (string) $firebaseUser->uid;

            if ($firebaseUid === '') {
                throw new \RuntimeException('Firebase Auth did not return a user id.');
            }

            $data['firebase_uid'] = $firebaseUid;
            $firebaseAuth->updateUser($firebaseUid, [
                'displayName' => trim((string) $this->request->getPost('firstName') . ' ' . (string) $this->request->getPost('lastName')),
            ]);

            // Ensure Firestore profile knows verification status
            $data['email_verified'] = false;

            try {
                $this->firestore->getDb()->collection('users')->document($firebaseUid)->set($data);

                if ($this->requiresEmailVerification()) {
                    $firebaseAuth->sendEmailVerificationLink($data['email']);
                }
            } catch (\Throwable $firestoreError) {
                $firebaseAuth->deleteUser($firebaseUid);
                throw $firestoreError;
            }
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to create Firebase Authentication user: ' . $e->getMessage());
        }

        $this->consumeInviteToken($inviteToken);

        return redirect()->to('/login')->with('success', 'Administrator Account Created');
    }

    /**
     * Logout user
     */
    public function logout() {
        session()->destroy();
        return redirect()->to('auth/login')->with('success', 'Logged out successfully');
    }

    public function firebaseToken()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Not logged in']);
        }
        $uid  = (string)(session()->get('userId') ?? '');
        $role = (string)(session()->get('role')   ?? '');
        if (!$uid) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No user ID']);
        }
        try {
            $factory = (new \Kreait\Firebase\Factory())
                ->withServiceAccount(WRITEPATH . 'credentials/firebase_key.json');
            $auth    = $factory->createAuth();
            $token   = $auth->createCustomToken($uid, ['role' => $role]);
            return $this->response->setJSON(['token' => $token->toString()]);
        } catch (\Throwable $e) {
            log_message('error', 'firebaseToken: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Token generation failed']);
        }
    }

    private function buildContactNumber(string $countryCode, string $displayValue): string
    {
        $configs = [
            'PH' => ['prefix' => '+63', 'groups' => [3, 3, 4]],
            'US' => ['prefix' => '+1', 'groups' => [3, 3, 4]],
            'SG' => ['prefix' => '+65', 'groups' => [4, 4]],
            'AU' => ['prefix' => '+61', 'groups' => [1, 4, 4]],
            'GB' => ['prefix' => '+44', 'groups' => [4, 3, 3]],
        ];

        $config = $configs[$countryCode] ?? $configs['PH'];
        $digits = preg_replace('/\D+/', '', $displayValue);
        $digits = substr($digits, 0, array_sum($config['groups']));

        $parts = [];
        $cursor = 0;

        foreach ($config['groups'] as $groupSize) {
            if ($cursor >= strlen($digits)) {
                break;
            }

            $parts[] = substr($digits, $cursor, $groupSize);
            $cursor += $groupSize;
        }

        if ($cursor < strlen($digits)) {
            $parts[] = substr($digits, $cursor);
        }

        $formatted = trim(implode(' ', array_filter($parts, static function ($part) {
            return $part !== '';
        })));

        return trim($config['prefix'] . ' ' . $formatted);
    }

    private function requiresEmailVerification(): bool
    {
        return filter_var(env('AUTH_REQUIRE_EMAIL_VERIFICATION', false), FILTER_VALIDATE_BOOL);
    }

    private static function resolveProfilePic(array $userData): ?string
    {
        $url = (string)($userData['profile_photo_url'] ?? '');
        if ($url !== '') return $url;

        $path = (string)($userData['profile_picture'] ?? '');
        if ($path !== '' && file_exists(FCPATH . $path)) return $path;

        return null;
    }

    private function applyRememberMeCookies(bool $rememberMe, string $email): void
    {
        $cookieConfig = config('Cookie');
        $sessionConfig = config('Session');

        if (! $rememberMe) {
            $this->response->deleteCookie('remember_email', $cookieConfig->domain, $cookieConfig->path, $cookieConfig->prefix);
            $this->response->deleteCookie('remember_me', $cookieConfig->domain, $cookieConfig->path, $cookieConfig->prefix);
            return;
        }

        $rememberSeconds = 60 * 60 * 24 * 30;

        $this->response->setCookie([
            'name'     => 'remember_email',
            'value'    => $email,
            'expire'   => $rememberSeconds,
            'path'     => $cookieConfig->path,
            'domain'   => $cookieConfig->domain,
            'secure'   => $cookieConfig->secure,
            'httponly' => false,
            'samesite' => $cookieConfig->samesite,
        ]);

        $this->response->setCookie([
            'name'     => 'remember_me',
            'value'    => '1',
            'expire'   => $rememberSeconds,
            'path'     => $cookieConfig->path,
            'domain'   => $cookieConfig->domain,
            'secure'   => $cookieConfig->secure,
            'httponly' => false,
            'samesite' => $cookieConfig->samesite,
        ]);

        // Re-issue session cookie with longer expiry when user opts into Remember Me.
        $this->response->setCookie([
            'name'     => $sessionConfig->cookieName,
            'value'    => session_id(),
            'expire'   => $rememberSeconds,
            'path'     => $cookieConfig->path,
            'domain'   => $cookieConfig->domain,
            'secure'   => $cookieConfig->secure,
            'httponly' => true,
            'samesite' => $cookieConfig->samesite,
        ]);
    }

}