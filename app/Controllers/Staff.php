<?php

namespace App\Controllers;

use App\Libraries\ArchiveService;
use App\Libraries\FirestoreService;
use App\Libraries\StorageService;
use App\Libraries\TrashService;
use App\Models\Model_Users;
use Google\Cloud\Core\Timestamp;

class Staff extends BaseController
{
    protected FirestoreService $firestore;
    protected \App\Models\Model_Users $usersModel;

    public function __construct() {
        $this->firestore = FirestoreService::getInstance();
        $this->usersModel = new Model_Users();
    }

    public function index()
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        helper('access');
        if (!pz_can('view_staff')) return redirect()->to('dashboard');

        try {
            // Fetch staff from Firestore, order by created_at ascending so new records appear at the bottom
            $db = $this->firestore->getDb();
            try {
                $staffCollection = $db->collection('users')->orderBy('created_at', 'ASCENDING');
            } catch (\Throwable $e) {
                // If the orderBy call isn't available for this client wrapper, fall back to unordered collection
                log_message('info', 'Staff::index - orderBy not available, falling back to client-side sort: ' . $e->getMessage());
                $staffCollection = $db->collection('users');
            }

            $staff = [];

            foreach ($staffCollection->documents() as $doc) {
                if ($doc->exists()) {
                    $staffData = $doc->data();
                    $staffData['id'] = $doc->id();
                    $staff[] = $staffData;
                }
            }

            // If we fell back to an unordered collection, try a client-side sort by created_at ascending
            if (!method_exists($staffCollection, 'orderBy')) {
                usort($staff, function ($a, $b) {
                    $ta = $a['created_at'] ?? $a['updated_at'] ?? null;
                    $tb = $b['created_at'] ?? $b['updated_at'] ?? null;

                    // Normalize possible Google Timestamp objects to integers if possible
                    if (is_object($ta) && method_exists($ta, 'getTimestamp')) {
                        $ta = $ta->getTimestamp();
                    } elseif (is_object($ta) && method_exists($ta, 'get')) {
                        // Google\Cloud\Core\Timestamp -> get() returns DateTime
                        try { $ta = $ta->get()->getTimestamp(); } catch (\Throwable $_) { $ta = null; }
                    }

                    if (is_object($tb) && method_exists($tb, 'getTimestamp')) {
                        $tb = $tb->getTimestamp();
                    } elseif (is_object($tb) && method_exists($tb, 'get')) {
                        try { $tb = $tb->get()->getTimestamp(); } catch (\Throwable $_) { $tb = null; }
                    }

                    $ta = $ta ?: 0;
                    $tb = $tb ?: 0;

                    return $ta <=> $tb; // ascending
                });
            }

            // — Build role option list from actual data —
            $roleOptions = [];
            foreach ($staff as $s) {
                $r = trim((string) ($s['role'] ?? ''));
                if ($r !== '' && !in_array($r, $roleOptions, true)) {
                    $roleOptions[] = $r;
                }
            }
            sort($roleOptions);

            // — Read filters —
            $filterLnRange = trim((string) ($this->request->getGet('ln_range') ?? ''));
            $filterRole    = trim((string) ($this->request->getGet('role_filter') ?? ''));

            // — Apply filters —
            if ($filterLnRange !== '' && str_contains($filterLnRange, '-')) {
                [$rangeStart, $rangeEnd] = explode('-', $filterLnRange, 2);
                $rangeStart = strtolower(trim($rangeStart));
                $rangeEnd   = strtolower(trim($rangeEnd));
                $staff = array_values(array_filter($staff, function ($s) use ($rangeStart, $rangeEnd) {
                    $letter = strtolower(substr(trim((string) ($s['last_name'] ?? '')), 0, 1));
                    return $letter !== '' && $letter >= $rangeStart && $letter <= $rangeEnd;
                }));
            }
            if ($filterRole !== '') {
                $staff = array_values(array_filter($staff, fn($s) =>
                    strcasecmp((string) ($s['role'] ?? ''), $filterRole) === 0));
            }

            $trashService    = new TrashService($this->firestore);
            $recentlyDeleted = $trashService->getTrashItems('staff');

            $data = [
                'pageTitle'       => 'Staff',
                'activePage'      => 'staff',
                'staff'           => $staff,
                'staffTotal'      => count($staff),
                'roleOptions'     => $roleOptions,
                'activeFilters'   => [
                    'ln_range'    => $filterLnRange,
                    'role_filter' => $filterRole,
                ],
                'recentlyDeleted' => $recentlyDeleted,
                'userName'        => session()->get('userName') ?? 'User',
                'role'            => session()->get('role') ?? 'Staff',
            ];

            return view('staff/staff_view', $data);
        } catch (\Exception $e) {
            log_message('error', 'Staff::index - ' . $e->getMessage());

            $data = [
                'pageTitle'       => 'Staff',
                'activePage'      => 'staff',
                'staff'           => [],
                'pagerOffset'     => 0,
                'roleOptions'     => [],
                'activeFilters'   => [],
                'recentlyDeleted' => [],
                'userName'        => session()->get('userName') ?? 'User',
                'role'            => session()->get('role') ?? 'Staff',
                'error'           => 'Unable to fetch staff',
            ];

            return view('staff/staff_view', $data);
        }
    }

    public function create()
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        helper('access');
        if (!pz_can('create_staff')) return redirect()->to('staff')->with('error', 'Only Administrators may create new user accounts.');

        if (strtolower($this->request->getMethod()) === 'post') {
            // Validate using named rule group 'staff'
            if (! $this->validate('staff')) {
                return redirect()->back()->withInput()->with('error', implode('; ', $this->validator->getErrors()));
            }

            try {
                log_message('info', 'Staff::create POST received');
                $lastName = trim((string) $this->request->getPost('last_name'));
                $firstName = trim((string) $this->request->getPost('first_name'));
                $email = trim((string) $this->request->getPost('email'));
                $role = trim((string) $this->request->getPost('role'));

                // Uniqueness check for email in Firestore
                $existing = $this->usersModel->getUserByEmail($email);
                if ($existing) {
                    return redirect()->back()->withInput()->with('error', 'Email is already in use.');
                }

                $now = new Timestamp(new \DateTime());
                $data = [
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'middle_name' => trim((string) ($this->request->getPost('middle_name') ?? '')),
                    'contact_no' => trim((string) ($this->request->getPost('contact_no') ?? '')),
                    'email' => $email,
                    'role' => $role,
                    'created_at' => $now,
                    'updated_at' => $now
                ];

                // Use explicit ID so we can reliably trace new documents
                $newId = bin2hex(random_bytes(8));

                // Handle profile picture upload
                $pic = $this->request->getFile('profile_picture');
                if ($pic && $pic->isValid() && !$pic->hasMoved()) {
                    $ext  = strtolower($pic->getClientExtension()) ?: 'jpg';
                    $mime = $pic->getMimeType();
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) && $pic->getSize() <= 2 * 1024 * 1024) {
                        $tmpDir = WRITEPATH . 'uploads/';
                        if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);
                        $tmpName = 'pfp_' . $newId . '_' . time() . '.' . $ext;
                        $pic->move($tmpDir, $tmpName);
                        try {
                            $photoUrl = (new StorageService())->upload($tmpDir . $tmpName, 'profile_pictures/' . $newId, $mime);
                            $data['profile_photo_url'] = $photoUrl;
                        } catch (\Throwable $e) {
                            log_message('error', 'Staff::create StorageService - ' . $e->getMessage());
                        } finally {
                            @unlink($tmpDir . $tmpName);
                        }
                    }
                }

                // Create Firebase Auth account and send temp password
                $tempPassword = $this->generateTempPassword();
                $firebaseAuth = $this->firestore->getAuth();
                $firebaseUser = $firebaseAuth->createUserWithEmailAndPassword($email, $tempPassword);
                $firebaseUid  = (string) $firebaseUser->uid;

                $firebaseAuth->updateUser($firebaseUid, [
                    'displayName' => trim($firstName . ' ' . $lastName),
                ]);

                $data['firebase_uid']            = $firebaseUid;
                $data['must_change_password']     = true;
                $data['temp_password_expires_at'] = time() + (15 * 60);

                // Use Firebase UID as Firestore document ID (consistent with Auth::register)
                $docRef = $this->firestore->getDb()->collection('users')->document($firebaseUid);

                try {
                    $docRef->set($data);
                } catch (\Throwable $firestoreErr) {
                    $firebaseAuth->deleteUser($firebaseUid);
                    throw $firestoreErr;
                }

                $this->sendWelcomeEmail($email, $firstName, $tempPassword);

                log_message('info', 'Staff::create success. UID: ' . $firebaseUid);

                return redirect()->to('staff')->with('success', 'Staff member created. Credentials sent to ' . $email . '.');
            } catch (\Exception $e) {
                log_message('error', 'Staff::create - ' . $e->getMessage());
                return redirect()->back()->withInput()->with('error', 'Failed to create staff member: ' . $e->getMessage());
            }
        }

        return view('staff/staff_form', [
            'pageTitle' => 'Add Staff',
            'activePage' => 'staff',
            'staff' => [],
            'userName' => session()->get('userName') ?? 'User',
            'role' => session()->get('role') ?? 'Staff'
        ]);
    }

    public function update($id)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        helper('access');
        if (!pz_can('edit_staff')) return redirect()->to('staff')->with('error', 'Access denied.');

        try {
            if (strtolower($this->request->getMethod()) === 'post') {
                // Validate using Config\Validation 'staff' group
                if (! $this->validate('staff')) {
                    return redirect()->back()->withInput()->with('error', implode('; ', $this->validator->getErrors()));
                }
                $now = new Timestamp(new \DateTime());
                $data = [
                    'last_name' => trim((string) $this->request->getPost('last_name')),
                    'first_name' => trim((string) $this->request->getPost('first_name')),
                    'middle_name' => trim((string) ($this->request->getPost('middle_name') ?? '')),
                    'contact_no' => trim((string) ($this->request->getPost('contact_no') ?? '')),
                    'email' => trim((string) $this->request->getPost('email')),
                    'role' => trim((string) $this->request->getPost('role')),
                    'must_change_password' => $this->request->getPost('must_change_password') ? true : false,
                    'updated_at' => $now
                ];
                // If email changed, ensure uniqueness
                $newEmail = $data['email'] ?? '';
                if ($newEmail !== '') {
                    $existing = $this->usersModel->getUserByEmail($newEmail);
                    if ($existing && $existing['id'] !== $id) {
                        return redirect()->back()->withInput()->with('error', 'Email is already in use by another staff member.');
                    }
                }

                // Handle profile picture upload
                $pic = $this->request->getFile('profile_picture');
                if ($pic && $pic->isValid() && !$pic->hasMoved()) {
                    $ext  = strtolower($pic->getClientExtension()) ?: 'jpg';
                    $mime = $pic->getMimeType();
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) && $pic->getSize() <= 2 * 1024 * 1024) {
                        $tmpDir = WRITEPATH . 'uploads/';
                        if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);
                        $tmpName = 'pfp_' . $id . '_' . time() . '.' . $ext;
                        $pic->move($tmpDir, $tmpName);
                        try {
                            $photoUrl = (new StorageService())->upload($tmpDir . $tmpName, 'profile_pictures/' . $id, $mime);
                            $data['profile_photo_url'] = $photoUrl;
                        } catch (\Throwable $e) {
                            log_message('error', 'Staff::update StorageService - ' . $e->getMessage());
                        } finally {
                            @unlink($tmpDir . $tmpName);
                        }
                    }
                }

                $this->firestore->getDb()->collection('users')->document($id)->set($data, ['merge' => true]);
                return redirect()->to('staff')->with('success', 'Staff member updated successfully!');
            }

            $staff = $this->firestore->getDb()->collection('users')->document($id)->snapshot();
            
            if (!$staff->exists()) {
                return redirect()->to('staff')->with('error', 'Staff member not found');
            }

            return view('staff/staff_form', [
                'pageTitle' => 'Edit Staff',
                'activePage' => 'staff',
                'staff' => $staff->data(),
                'staffId' => $id,
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'Staff'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Staff::update - ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update staff member');
        }
    }

    public function show($id)
    {
        try {
            $doc = $this->firestore->getDb()->collection('users')->document($id)->snapshot();
            if (!$doc->exists()) {
                return redirect()->to('staff')->with('error', 'Staff member not found');
            }

            return view('staff/staff_show', [
                'pageTitle' => 'Staff Detail',
                'activePage' => 'staff',
                'staff' => $doc->data(),
                'staffId' => $id,
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'Staff'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Staff::show - ' . $e->getMessage());
            return redirect()->to('staff')->with('error', 'Unable to fetch staff member');
        }
    }

    public function resendCredentials($id)
    {
        try {
            $doc = $this->firestore->getDb()->collection('users')->document($id)->snapshot();
            if (!$doc->exists()) {
                return redirect()->to('staff')->with('error', 'Staff member not found.');
            }

            $data      = $doc->data();
            $email     = $data['email']      ?? '';
            $firstName = $data['first_name'] ?? 'Staff';

            if ($email === '') {
                return redirect()->back()->with('error', 'Staff member has no email on record.');
            }

            $tempPassword = $this->generateTempPassword();

            // Firebase Auth UID may differ from the Firestore document ID for older records
            $firebaseUid  = $data['firebase_uid'] ?? $id;
            $firebaseAuth = $this->firestore->getAuth();
            $firebaseAuth->updateUser($firebaseUid, ['password' => $tempPassword]);

            // Reset must_change_password + fresh 15-minute expiry
            $this->firestore->getDb()->collection('users')->document($id)->update([
                ['path' => 'must_change_password',     'value' => true],
                ['path' => 'temp_password_expires_at', 'value' => time() + (15 * 60)],
            ]);

            $this->sendWelcomeEmail($email, $firstName, $tempPassword);

            return redirect()->to('staff/show/' . $id)->with('success', 'New credentials sent to ' . $email . '. The temporary password expires in 15 minutes.');
        } catch (\Throwable $e) {
            log_message('error', 'Staff::resendCredentials - ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to resend credentials: ' . $e->getMessage());
        }
    }

    public function deleted(): string|object
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        $items = (new TrashService($this->firestore))->getTrashItems('staff');
        return view('shared/module_deleted', [
            'pageTitle'  => 'Deleted Staff',
            'activePage' => 'staff',
            'userName'   => session()->get('userName') ?? 'User',
            'role'       => session()->get('role') ?? 'Staff',
            'module'     => 'staff',
            'title'      => 'Deleted Staff',
            'backUrl'    => base_url('staff'),
            'items'      => $items,
        ]);
    }

    public function archived(): string|object
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        $items = (new ArchiveService($this->firestore))->getArchivedItems('staff');
        return view('shared/module_archived', [
            'pageTitle'  => 'Archived Staff',
            'activePage' => 'staff',
            'userName'   => session()->get('userName') ?? 'User',
            'role'       => session()->get('role') ?? 'Staff',
            'module'     => 'staff',
            'title'      => 'Archived Staff',
            'backUrl'    => base_url('staff'),
            'items'      => $items,
        ]);
    }

    public function archive(): object
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        helper('access');
        if (!pz_can('edit_staff')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Access denied.']);
        }
        try {
            $id = trim((string)($this->request->getPost('id') ?? ''));
            if (!$id) return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing id']);

            $snap = $this->firestore->getDb()->collection('users')->document($id)->snapshot();
            $data = $snap->exists() ? $snap->data() : [];
            $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')) ?: ($data['email'] ?? $id);

            // Protect Administrator accounts — only Administrators may archive other Admins
            $targetRole  = (string)($data['role'] ?? '');
            $sessionRole = (string)(session()->get('role') ?? '');
            if ($targetRole === 'Administrator' && $sessionRole !== 'Administrator') {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Only Administrators can archive Administrator accounts.']);
            }

            (new ArchiveService($this->firestore))->moveToArchive('staff', $id, $name, $data, [], session()->get('userName'));
            $this->firestore->getDb()->collection('users')->document($id)->delete();

            return $this->response->setJSON(['success' => true, 'name' => $name]);
        } catch (\Throwable $e) {
            log_message('error', 'Staff::archive - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Archive failed']);
        }
    }

    public function delete($id = null)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        helper('access');
        if (!pz_can('edit_staff')) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Access denied.']);
            }
            return redirect()->to('staff')->with('error', 'Access denied.');
        }

        // If this is an AJAX/POST request, expect the ID in POST and return JSON
        if (strtolower($this->request->getMethod()) === 'post') {
            try {
                $idFromPost = $this->request->getPost('id') ?? null;
                if (!$idFromPost) {
                    // also accept JSON body
                    $json = $this->request->getJSON(true) ?? [];
                    $idFromPost = $json['id'] ?? null;
                }

                if (!$idFromPost) {
                    return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing id']);
                }

                // Soft-delete: snapshot then move to trash (do NOT delete Firebase Auth here)
                $snap = $this->firestore->getDb()->collection('users')->document($idFromPost)->snapshot();
                $data = $snap->exists() ? $snap->data() : [];
                $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')) ?: ($data['email'] ?? $idFromPost);

                // Protect Administrator accounts — only Administrators may delete other Admins
                $targetRole  = (string)($data['role'] ?? '');
                $sessionRole = (string)(session()->get('role') ?? '');
                if ($targetRole === 'Administrator' && $sessionRole !== 'Administrator') {
                    return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Only Administrators can delete Administrator accounts.']);
                }

                $trashId = (new TrashService($this->firestore))->moveToTrash('staff', $idFromPost, $name, $data, [], session()->get('userName'));

                // Only delete the Firestore doc (Auth deletion deferred to permanent delete)
                $this->firestore->getDb()->collection('users')->document($idFromPost)->delete();

                return $this->response->setJSON([
                    'success'    => true,
                    'message'    => 'Staff member deleted',
                    'trash_id'   => $trashId,
                    'name'       => $name,
                    'deleted_by' => session()->get('userName') ?? 'System',
                    'deleted_at' => time(),
                ]);
            } catch (\Exception $e) {
                log_message('error', 'Staff::delete (ajax) - ' . $e->getMessage());
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Failed to delete staff member']);
            }
        }

        // Fallback: traditional GET with segment - redirect back to staff list
        if ($id !== null) {
            try {
                // Soft-delete: snapshot then move to trash (do NOT delete Firebase Auth here)
                $snap = $this->firestore->getDb()->collection('users')->document($id)->snapshot();
                $data = $snap->exists() ? $snap->data() : [];
                $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')) ?: ($data['email'] ?? $id);

                $targetRole  = (string)($data['role'] ?? '');
                $sessionRole = (string)(session()->get('role') ?? '');
                if ($targetRole === 'Administrator' && $sessionRole !== 'Administrator') {
                    return redirect()->to('staff')->with('error', 'Only Administrators can delete Administrator accounts.');
                }

                (new TrashService($this->firestore))->moveToTrash('staff', $id, $name, $data, [], session()->get('userName'));

                $this->firestore->getDb()->collection('users')->document($id)->delete();
                return redirect()->to('staff')->with('success', 'Staff member deleted successfully!');
            } catch (\Exception $e) {
                log_message('error', 'Staff::delete - ' . $e->getMessage());
                return redirect()->back()->with('error', 'Failed to delete staff member');
            }
        }

        return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method not allowed']);
    }

    private function generateTempPassword(): string
    {
        $chars   = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $special = '!@#$%';
        $pw = '';
        for ($i = 0; $i < 10; $i++) {
            $pw .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $pw .= $special[random_int(0, strlen($special) - 1)];
        $arr = str_split($pw);
        shuffle($arr);
        return implode('', $arr);
    }

    private function sendWelcomeEmail(string $email, string $firstName, string $tempPassword): void
    {
        $mailer      = service('email');
        $emailConfig = config('Email');

        $mailer->initialize([
            'protocol'       => (string) env('EMAIL_PROTOCOL',       $emailConfig->protocol),
            'mailPath'       => (string) env('EMAIL_MAIL_PATH',      $emailConfig->mailPath ?? ''),
            'SMTPHost'       => (string) env('EMAIL_SMTP_HOST',      $emailConfig->SMTPHost),
            'SMTPUser'       => (string) env('EMAIL_SMTP_USER',      $emailConfig->SMTPUser),
            'SMTPPass'       => (string) env('EMAIL_SMTP_PASS',      $emailConfig->SMTPPass),
            'SMTPPort'       => (int)    env('EMAIL_SMTP_PORT',      $emailConfig->SMTPPort),
            'SMTPCrypto'     => (string) env('EMAIL_SMTP_CRYPTO',    $emailConfig->SMTPCrypto),
            'SMTPAuthMethod' => (string) env('EMAIL_SMTP_AUTH_METHOD', $emailConfig->SMTPAuthMethod ?? 'login'),
            'SMTPTimeout'    => (int)    env('EMAIL_SMTP_TIMEOUT',   $emailConfig->SMTPTimeout ?? 5),
            'mailType'       => 'html',
            'charset'        => 'UTF-8',
            'wordWrap'       => true,
            'validate'       => false,
            'CRLF'           => (string) env('EMAIL_CRLF',    $emailConfig->CRLF    ?? "\r\n"),
            'newline'        => (string) env('EMAIL_NEWLINE', $emailConfig->newline  ?? "\r\n"),
        ]);

        $fromEmail = $emailConfig->fromEmail ?: (string) env('EMAIL_FROM', 'no-reply@primazentra.local');
        $fromName  = $emailConfig->fromName  ?: (string) env('EMAIL_FROM_NAME', 'PrimaZentra');

        $mailer->setFrom($fromEmail, $fromName);
        $mailer->setTo($email);
        $mailer->setSubject('Your PrimaZentra Account Credentials');
        $mailer->setMessage(view('emails/welcome_credentials', [
            'firstName'    => $firstName,
            'email'        => $email,
            'tempPassword' => $tempPassword,
        ]));

        if (!$mailer->send(false)) {
            throw new \RuntimeException('Email send failed: ' . $mailer->printDebugger(['headers']));
        }
    }
}
