<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;

class Auth extends BaseController {
    protected $firestore;

    public function __construct() {
        $this->firestore = new FirestoreService();
    }

    public function login() {
        return view('auth/login');
    }

    // Inside Auth.php
    public function processLogin() {
        $email = $this->request->getPost('email');
        $userModel = new \App\Models\Model_Users();
        
        $user = $userModel->getUserByEmail($email);

        if ($user) {
            session()->set([
                'isLoggedIn' => true,
                'userId'     => $user['id'],
                'userName'   => $user['data']['first_name'] . ' ' . $user['data']['last_name'],
                'role'       => $user['data']['role'],
            ]);
            return redirect()->to('/dashboard');
        }

        return redirect()->back()->with('error', 'User not found.');
    }

    public function register() {
        return view('auth/register');
    }

    public function processRegister() {
        // Logic for Executive self-registration
        $data = [
            'first_name'  => $this->request->getPost('firstName'),
            'last_name'   => $this->request->getPost('lastName'),
            'middle_name' => $this->request->getPost('middleName'),
            'email'      => $this->request->getPost('email'),
            'role'       => 'executive', // Hardcoded as per your rule
            'created_at'  => new \Google\Cloud\Core\Timestamp(new \DateTime()),
            'must_change_password' => false
        ];

        $this->firestore->getDb()->collection('users')->add($data);
        return redirect()->to('/login')->with('success', 'Executive Account Created');
    }
}