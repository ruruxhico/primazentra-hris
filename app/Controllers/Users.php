<?php

namespace App\Controllers;

// Import your new Model
use App\Models\Model_Users;

class Users extends BaseController
{
    protected $userModel;

    public function __construct() {
        // Initialize the model instead of the library directly
        $this->userModel = new Model_Users();
    }

    public function index()
    {
        try {
            // Using the new library/model logic to fetch all documents
            // We'll use the firestore instance inherited in the model
            $staffCollection = $this->userModel->getFirestoreInstance()->getDb()->collection('users');
            
            $staff = [];
            foreach ($staffCollection->documents() as $doc) {
                if ($doc->exists()) {
                    $staffData = $doc->data();
                    $staffData['id'] = $doc->id();
                    $staff[] = $staffData;
                }
            }

            $data = [
                'pageTitle'  => 'Staff',
                'activePage' => 'staff',
                'staff'      => $staff,
                'userName'   => session()->get('userName') ?? 'User',
                'role'       => session()->get('role') ?? 'Staff'
            ];

            return view('dashboard/staff', $data);
        } catch (\Exception $e) {
            log_message('error', 'Users::index - ' . $e->getMessage());
            
            $data = [
                'pageTitle'  => 'Staff',
                'activePage' => 'staff',
                'staff'      => [],
                'userName'   => session()->get('userName') ?? 'User',
                'role'       => session()->get('role') ?? 'Staff',
                'error'      => 'Unable to fetch staff: ' . $e->getMessage()
            ];

            return view('dashboard/staff', $data);
        }
    }

    public function create()
    {
        if ($this->request->getMethod() === 'post') {
            try {
                $data = [
                    'last_name'   => $this->request->getPost('last_name'), // Match your Firestore naming convention
                    'first_name'  => $this->request->getPost('first_name'),
                    'middle_name' => $this->request->getPost('middle_name') ?? '',
                    'email'      => $this->request->getPost('email'),
                    'role'       => $this->request->getPost('role'),
                    'created_at'  => new \Google\Cloud\Core\Timestamp(new \DateTime()),
                ];

                // Using the model method to add staff member
                $this->userModel->addStaffMember($data);
                
                return redirect()->to('staff')->with('success', 'Staff member created successfully!');
            } catch (\Exception $e) {
                log_message('error', 'Users::create - ' . $e->getMessage());
                return redirect()->back()->withInput()->with('error', 'Failed: ' . $e->getMessage());
            }
        }

        return view('dashboard/staff_form', [
            'pageTitle'  => 'Add Staff',
            'activePage' => 'staff',
            'userName'   => session()->get('userName') ?? 'User',
            'role'       => session()->get('role') ?? 'Staff'
        ]);
    }

    // ... Update and Delete follow the same pattern
}