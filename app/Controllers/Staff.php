<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;

class Staff extends BaseController
{
    protected FirestoreService $firestore;

    public function __construct() {
        $this->firestore = new FirestoreService();
    }

    public function index()
    {
        try {
            // Fetch staff from Firestore
            $staffCollection = $this->firestore->getDb()->collection('users');
            $staff = [];
            
            foreach ($staffCollection->documents() as $doc) {
                if ($doc->exists()) {
                    $staffData = $doc->data();
                    $staffData['id'] = $doc->id();
                    $staff[] = $staffData;
                }
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
        if ($this->request->getMethod() === 'post') {
            try {
                $data = [
                    'last_name' => $this->request->getPost('last_name'),
                    'first_name' => $this->request->getPost('first_name'),
                    'middle_name' => $this->request->getPost('middle_name') ?? '',
                    'email' => $this->request->getPost('email'),
                    'role' => $this->request->getPost('role'),
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $this->firestore->getDb()->collection('users')->add($data);
                return redirect()->to('staff')->with('success', 'Staff member created successfully!');
            } catch (\Exception $e) {
                log_message('error', 'Staff::create - ' . $e->getMessage());
                return redirect()->back()->withInput()->with('error', 'Failed to create staff member');
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
            if ($this->request->getMethod() === 'post') {
                $data = [
                    'last_name' => $this->request->getPost('last_name'),
                    'first_name' => $this->request->getPost('first_name'),
                    'middle_name' => $this->request->getPost('middle_name') ?? '',
                    'email' => $this->request->getPost('email'),
                    'role' => $this->request->getPost('role'),
                    'updated_at' => now()
                ];

                $this->firestore->getDb()->collection('users')->document($id)->update($data);
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

    public function delete($id)
    {
        try {
            $this->firestore->getDb()->collection('users')->document($id)->delete();
            return redirect()->to('staff')->with('success', 'Staff member deleted successfully!');
        } catch (\Exception $e) {
            log_message('error', 'Staff::delete - ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete staff member');
        }
    }
}
