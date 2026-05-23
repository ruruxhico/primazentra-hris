<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;
use Google\Cloud\Core\Timestamp;

class Auth extends BaseController {
    protected FirestoreService $firestore;

    public function __construct() {
        $this->firestore = new FirestoreService();
    }

    public function login() {
        $rememberedEmail = (string) $this->request->getCookie('remember_email');
        $rememberChecked = $rememberedEmail !== '' || $this->request->getCookie('remember_me') === '1';

        return view('auth/login', [
            'rememberedEmail' => $rememberedEmail,
            'rememberChecked' => $rememberChecked,
        ]);
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

        // Email verification is disabled for now so new users can sign in immediately.
        // To re-enable later, set AUTH_REQUIRE_EMAIL_VERIFICATION=true in your .env
        // and uncomment the verification block below.
        /*
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
        */

        $userModel = new \App\Models\Model_Users();
        $user = $userModel->getUserById($firebaseUid) ?? $userModel->getUserByEmail($email);

        if (!$user || !is_array($user['data'] ?? null)) {
            return redirect()->back()->withInput()->with('error', 'Account profile not found. Please contact support.');
        }

        $userData = $user['data'];

        session()->set([
            'isLoggedIn' => true,
            'userId'     => $user['id'],
            'userName'   => trim((string) ($userData['first_name'] ?? '') . ' ' . (string) ($userData['last_name'] ?? '')),
            'role'       => $userData['role'],
        ]);

        $this->applyRememberMeCookies($rememberMe, $email);

        return redirect()->to('/dashboard');
    }

    public function register() {
        return view('auth/register');
    }

    public function processRegister() {
        // Logic for Executive self-registration
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
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'        => 'executive', // Hardcoded as per your rule
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

                // Keep this block disabled for now so development accounts are usable immediately.
                // To apply later, uncomment it or move it behind AUTH_REQUIRE_EMAIL_VERIFICATION=true.
                /*
                if ($this->requiresEmailVerification()) {
                    $firebaseAuth->sendEmailVerificationLink($data['email']);
                }
                */
            } catch (\Throwable $firestoreError) {
                $firebaseAuth->deleteUser($firebaseUid);
                throw $firestoreError;
            }
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to create Firebase Authentication user: ' . $e->getMessage());
        }

        return redirect()->to('/login')->with('success', 'Executive Account Created');
    }

    /**
     * Logout user
     */
    public function logout() {
        session()->destroy();
        return redirect()->to('auth/login')->with('success', 'Logged out successfully');
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