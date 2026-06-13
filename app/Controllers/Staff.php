<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;
use App\Models\Model_Users;
use Google\Cloud\Core\Timestamp;

class Staff extends BaseController
{
    protected FirestoreService $firestore;
    protected \App\Models\Model_Users $usersModel;

    public function __construct() {
        $this->firestore = new FirestoreService();
        $this->usersModel = new Model_Users();
    }

    public function index()
    {
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

            $perPage     = config('Pager')->perPage;
            $currentPage = max(1, (int) ($this->request->getGet('page') ?? 1));
            $total       = count($staff);

            $pager = service('pager');
            $pager->store('staff', $currentPage, $perPage, $total);

            $offset    = ($currentPage - 1) * $perPage;
            $pagedStaff = array_slice($staff, $offset, $perPage);

            $data = [
                'pageTitle'     => 'Staff',
                'activePage'    => 'staff',
                'staff'         => $pagedStaff,
                'pagerOffset'   => $offset,
                'roleOptions'   => $roleOptions,
                'activeFilters' => [
                    'ln_range'    => $filterLnRange,
                    'role_filter' => $filterRole,
                ],
                'userName' => session()->get('userName') ?? 'User',
                'role'     => session()->get('role') ?? 'Staff',
            ];

            return view('staff/staff_view', $data);
        } catch (\Exception $e) {
            log_message('error', 'Staff::index - ' . $e->getMessage());

            $data = [
                'pageTitle'     => 'Staff',
                'activePage'    => 'staff',
                'staff'         => [],
                'pagerOffset'   => 0,
                'roleOptions'   => [],
                'activeFilters' => [],
                'userName'      => session()->get('userName') ?? 'User',
                'role'          => session()->get('role') ?? 'Staff',
                'error'         => 'Unable to fetch staff',
            ];

            return view('staff/staff_view', $data);
        }
    }

   public function create()
{
    if (strtolower($this->request->getMethod()) === 'post') {
        if (! $this->validate('staff')) {
            return redirect()->back()->withInput()->with('error', implode('; ', $this->validator->getErrors()));
        }

        try {
            log_message('info', 'Staff::create POST received');
            $lastName  = trim((string) $this->request->getPost('last_name'));
            $firstName = trim((string) $this->request->getPost('first_name'));
            $email     = trim((string) $this->request->getPost('email'));
            $role      = trim((string) $this->request->getPost('role'));

            // Uniqueness check for email in Firestore
            $existing = $this->usersModel->getUserByEmail($email);
            if ($existing) {
                return redirect()->back()->withInput()->with('error', 'Email is already in use.');
            }

            // ─── Generate a temporary password ────────────────────────────────
            $tempPassword = $this->generateTempPassword();

            // ─── Create Firebase Auth account with temp password ──────────────
            $firebaseAuth = $this->firestore->getAuth();

            try {
                $firebaseUser = $firebaseAuth->createUserWithEmailAndPassword($email, $tempPassword);
                $firebaseUid  = (string) $firebaseUser->uid;

                if ($firebaseUid === '') {
                    throw new \RuntimeException('Firebase Auth did not return a user ID.');
                }

                $firebaseAuth->updateUser($firebaseUid, [
                    'displayName' => trim($firstName . ' ' . $lastName),
                ]);
            } catch (\Throwable $e) {
                log_message('error', 'Staff::create Firebase Auth - ' . $e->getMessage());
                return redirect()->back()->withInput()->with('error', 'Failed to create auth account: ' . $e->getMessage());
            }

            $now = new Timestamp(new \DateTime());

            $data = [
                'last_name'           => $lastName,
                'first_name'          => $firstName,
                'middle_name'         => trim((string) ($this->request->getPost('middle_name') ?? '')),
                'contact_no'          => trim((string) ($this->request->getPost('contact_no')  ?? '')),
                'email'               => $email,
                'role'                => $role,
                'must_change_password'=> true,   // always true for new staff
                'is_verified'         => true,   // active immediately since they have credentials
                'firebase_uid'        => $firebaseUid,
                'created_at'          => $now,
                'updated_at'          => $now,
            ];

            // Use Firebase UID as document ID (required for mobile login lookup)
            $docRef = $this->firestore->getDb()->collection('users')->document($firebaseUid);
            $docRef->set($data);

            // Verify write succeeded
            $snapshot = $docRef->snapshot();
            if (!$snapshot->exists()) {
                throw new \RuntimeException('Firestore write verification failed.');
            }

            // ─── Send welcome email with temp password ────────────────────────
            $this->sendWelcomeEmail($email, $firstName, $tempPassword);

            log_message('info', 'Staff::create success. Document ID: ' . $firebaseUid);

            return redirect()->to('staff')->with('success', 'Staff member created successfully! Login credentials sent via email.');
        } catch (\Exception $e) {
            log_message('error', 'Staff::create - ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to create staff member: ' . $e->getMessage());
        }
    }

    return view('staff/staff_form', [
        'pageTitle'  => 'Add Staff',
        'activePage' => 'staff',
        'staff'      => [],
        'userName'   => session()->get('userName') ?? 'User',
        'role'       => session()->get('role') ?? 'Staff'
    ]);
}

// ─── private helpers ──────────────────────────────────────────────────────────

/**
 * Generate a readable but secure temporary password
 * e.g. "Pz-7Kx9mQ2r"
 */
private function generateTempPassword(): string
{
    $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // no O, I to avoid confusion
    $lower   = 'abcdefghijkmnopqrstuvwxyz'; // no l
    $numbers = '23456789'; // no 0, 1
    $all     = $upper . $lower . $numbers;

    $password  = $upper[random_int(0, strlen($upper) - 1)];
    $password .= $lower[random_int(0, strlen($lower) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];

    for ($i = 0; $i < 7; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }

    return str_shuffle($password); // e.g. "Pz7Kx9mQ2r"
}

private function sendWelcomeEmail(string $to, string $firstName, string $tempPassword): void
{
    $emailConfig = config('Email');
    $mailer      = service('email');

    $mailer->initialize([
        'protocol'       => $emailConfig->protocol,
        'SMTPHost'       => $emailConfig->SMTPHost,
        'SMTPUser'       => $emailConfig->SMTPUser,
        'SMTPPass'       => $emailConfig->SMTPPass,
        'SMTPPort'       => $emailConfig->SMTPPort,
        'SMTPCrypto'     => $emailConfig->SMTPCrypto,
        'SMTPAuthMethod' => $emailConfig->SMTPAuthMethod,
        'SMTPTimeout'    => $emailConfig->SMTPTimeout,
        'mailType'       => 'html',
        'charset'        => $emailConfig->charset,
        'wordWrap'       => true,
        'validate'       => false,
        'CRLF'           => $emailConfig->CRLF,
        'newline'        => $emailConfig->newline,
    ]);

    $mailer->setFrom($emailConfig->fromEmail, $emailConfig->fromName);
    $mailer->setTo($to);
    $mailer->setSubject('Welcome to PrimaZentra – Your Login Credentials');
    $mailer->setMessage(view('emails/welcome_credentials', [
        'firstName'    => $firstName,
        'email'        => $to,
        'tempPassword' => $tempPassword,
    ]));

    $result = $mailer->send(false);
    log_message($result ? 'info' : 'error', 'Welcome email send result: ' . ($result ? 'SUCCESS' : 'FAILED') . ' for ' . $to);
}


    public function update($id)
    {
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

    public function delete($id = null)
    {
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

                // Delete from Firebase Auth too (doc ID = firebase_uid)
                try {
                    $this->firestore->getAuth()->deleteUser($idFromPost);
                } catch (\Throwable $e) {
                    log_message('warning', 'Staff::delete - Firebase Auth user not found or already deleted: ' . $e->getMessage());
                }

                $this->firestore->getDb()->collection('users')->document($idFromPost)->delete();

                return $this->response->setJSON(['success' => true, 'message' => 'Staff member deleted']);
            } catch (\Exception $e) {
                log_message('error', 'Staff::delete (ajax) - ' . $e->getMessage());
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Failed to delete staff member']);
            }
        }



         // Fallback: traditional GET with segment - redirect back to staff list
        if ($id !== null) {
            try {
                // Delete from Firebase Auth too (doc ID = firebase_uid)
                try {
                    $this->firestore->getAuth()->deleteUser($id);
                } catch (\Throwable $e) {
                    log_message('warning', 'Staff::delete - Firebase Auth user not found or already deleted: ' . $e->getMessage());
                }

                $this->firestore->getDb()->collection('users')->document($id)->delete();
                return redirect()->to('staff')->with('success', 'Staff member deleted successfully!');
            } catch (\Exception $e) {
                log_message('error', 'Staff::delete - ' . $e->getMessage());
                return redirect()->back()->with('error', 'Failed to delete staff member');
            }
        }

        return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method not allowed']);
    }
}
