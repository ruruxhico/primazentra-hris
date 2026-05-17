<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;
use Google\Cloud\Core\Timestamp;

class Staff extends BaseController
{
    protected FirestoreService $firestore;

    public function __construct() {
        $this->firestore = new FirestoreService();
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

            $data = [
                'pageTitle' => 'Staff',
                'activePage' => 'staff',
                'staff' => $staff,
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'Staff'
            ];

            return view('staff/staff_view', $data);
        } catch (\Exception $e) {
            log_message('error', 'Staff::index - ' . $e->getMessage());
            
            $data = [
                'pageTitle' => 'Staff',
                'activePage' => 'staff',
                'staff' => [],
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'Staff',
                'error' => 'Unable to fetch staff'
            ];

            return view('staff/staff_view', $data);
        }
    }

    public function create()
    {
        if (strtolower($this->request->getMethod()) === 'post') {
            try {
                log_message('info', 'Staff::create POST received');
                $lastName = trim((string) $this->request->getPost('last_name'));
                $firstName = trim((string) $this->request->getPost('first_name'));
                $email = trim((string) $this->request->getPost('email'));
                $role = trim((string) $this->request->getPost('role'));

                if ($lastName === '' || $firstName === '' || $email === '' || $role === '') {
                    return redirect()->back()->withInput()->with('error', 'Please fill in all required fields.');
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
