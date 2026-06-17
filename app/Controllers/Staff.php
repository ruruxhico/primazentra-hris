<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;
use App\Libraries\TrashService;
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

            $trashService    = new TrashService($this->firestore);
            $recentlyDeleted = $trashService->getTrashItems('staff');

            $data = [
                'pageTitle'       => 'Staff',
                'activePage'      => 'staff',
                'staff'           => $pagedStaff,
                'pagerOffset'     => $offset,
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
                    'must_change_password' => $this->request->getPost('must_change_password') ? true : false,
                    'created_at' => $now,
                    'updated_at' => $now
                ];

                // Use explicit ID so we can reliably trace new documents
                $newId = bin2hex(random_bytes(8));
                $docRef = $this->firestore->getDb()->collection('users')->document($newId);
                $docRef->set($data);

                // Verify write succeeded
                $snapshot = $docRef->snapshot();
                if (!$snapshot->exists()) {
                    throw new \RuntimeException('Firestore write verification failed.');
                }

                log_message('info', 'Staff::create success. Document ID: ' . $newId);

                return redirect()->to('staff')->with('success', 'Staff member created successfully!');
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

                // Soft-delete: snapshot then move to trash (do NOT delete Firebase Auth here)
                $snap = $this->firestore->getDb()->collection('users')->document($idFromPost)->snapshot();
                $data = $snap->exists() ? $snap->data() : [];
                $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')) ?: ($data['email'] ?? $idFromPost);
                (new TrashService($this->firestore))->moveToTrash('staff', $idFromPost, $name, $data, [], session()->get('userName'));

                // Only delete the Firestore doc (Auth deletion deferred to permanent delete)
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
                // Soft-delete: snapshot then move to trash (do NOT delete Firebase Auth here)
                $snap = $this->firestore->getDb()->collection('users')->document($id)->snapshot();
                $data = $snap->exists() ? $snap->data() : [];
                $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')) ?: ($data['email'] ?? $id);
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
}
