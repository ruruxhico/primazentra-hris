<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;
use Google\Cloud\Core\Timestamp;

class VerifyAccount extends BaseController
{
    protected FirestoreService $firestore;

    public function __construct()
    {
        $this->firestore = FirestoreService::getInstance();
    }

    public function showForm(string $uid)
    {
        try {
            $doc = $this->firestore->getDb()->collection('users')->document($uid)->snapshot();

            if (!$doc->exists()) {
                return redirect()->to('/login')->with('error', 'Invalid or expired verification link.');
            }

            $data = $doc->data();

            if (!empty($data['is_verified'])) {
                return redirect()->to('/login')->with('error', 'This account has already been verified.');
            }

            return view('auth/verify_account', [
                'pageTitle' => 'Verify Account',
                'uid'       => $uid,
                'email'     => $data['email'] ?? '',
                'firstName' => $data['first_name'] ?? '',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'VerifyAccount::showForm - ' . $e->getMessage());
            return redirect()->to('/login')->with('error', 'Unable to load verification page.');
        }
    }

    public function setPassword(string $uid)
    {
        $password        = (string) $this->request->getPost('password');
        $confirmPassword = (string) $this->request->getPost('confirm_password');

        if ($password === '' || strlen($password) < 8) {
            return redirect()->back()->withInput()->with('error', 'Password must be at least 8 characters.');
        }

        if ($password !== $confirmPassword) {
            return redirect()->back()->withInput()->with('error', 'Passwords do not match.');
        }

        try {
            $doc = $this->firestore->getDb()->collection('users')->document($uid)->snapshot();

            if (!$doc->exists()) {
                return redirect()->to('/login')->with('error', 'Invalid or expired verification link.');
            }

            $userData = $doc->data();

            if (!empty($userData['is_verified'])) {
                return redirect()->to('/login')->with('error', 'This account has already been verified.');
            }

            // Update Firebase Auth password
            $this->firestore->getAuth()->changeUserPassword($uid, $password);

            // Mark account verified and clear the force-change flag
            $this->firestore->getDb()->collection('users')->document($uid)->set([
                'is_verified'          => true,
                'must_change_password' => false,
                'updated_at'           => new Timestamp(new \DateTime()),
            ], ['merge' => true]);

            return redirect()->to('/verification-success');
        } catch (\Throwable $e) {
            log_message('error', 'VerifyAccount::setPassword - ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Unable to verify account: ' . $e->getMessage());
        }
    }

    public function verificationSuccess()
    {
        return view('auth/verification_success', [
            'pageTitle' => 'Account Verified',
        ]);
    }
}
