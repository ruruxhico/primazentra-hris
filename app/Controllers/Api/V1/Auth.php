<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Libraries\FirestoreService;
use App\Models\Model_Users;

/**
 * POST /api/v1/auth/login  {email, password}
 *
 * The main app is the single identity provider for the HRIS. The Attendance
 * and Payroll apps send the credentials a user typed into their login form
 * here and receive the user's identity and role. They never read the
 * `users` collection themselves.
 */
class Auth extends BaseController
{
    public function login()
    {
        $body     = $this->request->getJSON(true) ?? $this->request->getPost();
        $email    = strtolower(trim((string) ($body['email'] ?? '')));
        $password = (string) ($body['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->error(422, 'validation', 'Email and password are required.');
        }

        $firestore = FirestoreService::getInstance();

        try {
            $uid = (string) ($firestore->getAuth()->signInWithEmailAndPassword($email, $password)->firebaseUserId() ?? '');
        } catch (\Throwable $e) {
            $uid = '';
        }
        if ($uid === '') {
            return $this->error(401, 'invalid_credentials', 'Invalid email or password.');
        }

        try {
            $users = new Model_Users();
            $user  = $users->getUserById($uid) ?? $users->getUserByEmail($email);
        } catch (\Throwable $e) {
            log_message('error', 'API auth profile lookup: ' . $e->getMessage());

            return $this->error(503, 'unavailable', 'Unable to load account profile.');
        }

        $data = is_array($user['data'] ?? null) ? $user['data'] : null;
        if ($data === null) {
            return $this->error(403, 'no_profile', 'Account profile not found.');
        }
        if (isset($data['is_verified']) && $data['is_verified'] === false) {
            return $this->error(403, 'unverified', 'Please verify your account first.');
        }
        if (! empty($data['must_change_password'])) {
            return $this->error(403, 'must_change_password', 'Log in to the main PrimaZentra app once to set your password.');
        }

        return $this->response->setJSON(['api_version' => 'v1', 'data' => [
            'uid'   => $user['id'],
            'email' => (string) ($data['email'] ?? $email),
            'name'  => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
            'role'  => (string) ($data['role'] ?? ''),
        ]]);
    }

    private function error(int $status, string $code, string $message)
    {
        return $this->response->setStatusCode($status)->setJSON(['error' => ['code' => $code, 'message' => $message]]);
    }
}
