<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;
use App\Models\Model_Users;
use Google\Cloud\Core\Timestamp;

class Profile extends BaseController
{
    protected FirestoreService $firestore;
    protected Model_Users $usersModel;

    public function __construct()
    {
        $this->firestore  = FirestoreService::getInstance();
        $this->usersModel = new Model_Users();
    }

    public function index()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('auth/login');
        }

        $uid  = (string) session()->get('userId');
        $user = [];
        try {
            $raw = $this->usersModel->getUserById($uid);
            if ($raw) {
                $user = is_array($raw['data'] ?? null) ? $raw['data'] : [];
                $user['id'] = $raw['id'] ?? $uid;
            }
        } catch (\Throwable $e) {
            log_message('error', 'Profile::index - ' . $e->getMessage());
        }

        // Merge session email into user array so the view can always display it
        if (empty($user['email'])) {
            $user['email'] = (string)(session()->get('userEmail') ?? '');
        }

        $profilePicture = self::resolveProfilePic($user);

        // Keep session in sync so the header shows the photo on other pages too
        if ($profilePicture !== null) {
            session()->set('profilePicture', $profilePicture);
        }

        return view('profile/profile_view', [
            'pageTitle'      => 'My Profile',
            'activePage'     => 'profile',
            'userName'       => session()->get('userName') ?? 'User',
            'role'           => session()->get('role') ?? 'User',
            'user'           => $user,
            'profilePicture' => $profilePicture,
        ]);
    }

    public function update()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('auth/login');
        }

        $uid        = (string) session()->get('userId');
        $firstName  = strtoupper(trim((string) $this->request->getPost('first_name')));
        $middleName = strtoupper(trim((string) $this->request->getPost('middle_name')));
        $lastName   = strtoupper(trim((string) $this->request->getPost('last_name')));

        if ($firstName === '' || $lastName === '') {
            return redirect()->back()->with('error', 'First and last name are required.');
        }

        try {
            $this->firestore->getDb()->collection('users')->document($uid)->set([
                'first_name'  => $firstName,
                'middle_name' => $middleName,
                'last_name'   => $lastName,
                'updated_at'  => new Timestamp(new \DateTime()),
            ], ['merge' => true]);

            session()->set('userName', trim($firstName . ' ' . $lastName));
        } catch (\Throwable $e) {
            log_message('error', 'Profile::update - ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update profile. Please try again.');
        }

        return redirect()->to('profile')->with('success', 'Profile updated successfully.');
    }

    public function changePassword()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('auth/login');
        }

        return view('profile/change_password_view', [
            'pageTitle'  => 'Change Password',
            'activePage' => 'profile',
            'userName'   => session()->get('userName') ?? 'User',
            'role'       => session()->get('role') ?? 'User',
        ]);
    }

    public function processChangePassword()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('auth/login');
        }

        $currentPassword = (string) $this->request->getPost('current_password');
        $newPassword     = (string) $this->request->getPost('password');
        $confirmPassword = (string) $this->request->getPost('confirm_password');

        if (strlen($newPassword) < 8) {
            return redirect()->back()->with('error', 'New password must be at least 8 characters.');
        }

        if ($newPassword !== $confirmPassword) {
            return redirect()->back()->with('error', 'Passwords do not match.');
        }

        $uid = (string) session()->get('userId');

        // Verify the current password by re-authenticating
        try {
            // Email is stored in session at login; fall back to Firestore if missing (old sessions)
            $email = (string)(session()->get('userEmail') ?? '');
            if ($email === '') {
                $raw   = $this->usersModel->getUserById($uid);
                $email = (string)(($raw['data'] ?? [])['email'] ?? '');
            }
            if ($email === '') {
                return redirect()->back()->with('error', 'Unable to verify your identity. Please log out and back in, then try again.');
            }
            $this->firestore->getAuth()->signInWithEmailAndPassword($email, $currentPassword);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Current password is incorrect.');
        }

        try {
            $this->firestore->getAuth()->updateUser($uid, ['password' => $newPassword]);
        } catch (\Throwable $e) {
            log_message('error', 'Profile::processChangePassword - ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update password. Please try again.');
        }

        return redirect()->to('profile')->with('success', 'Password changed successfully.');
    }

    public function uploadPhoto()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $file = $this->request->getFile('profile_picture');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No valid file uploaded.']);
        }

        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowed, true)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Only JPG, PNG, GIF, or WebP images are allowed.']);
        }

        if ($file->getSizeByUnit('mb') > 5) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Image must be under 5 MB.']);
        }

        $uid  = (string) session()->get('userId');
        $ext  = strtolower($file->getClientExtension()) ?: 'jpg';
        $mime = $file->getMimeType();

        // Move to a temp location so StorageService can read it
        $tmpDir  = WRITEPATH . 'uploads/';
        if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);
        $tmpName = 'pfp_' . $uid . '_' . time() . '.' . $ext;
        if (!$file->move($tmpDir, $tmpName)) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Failed to process image.']);
        }

        $tmpPath     = $tmpDir . $tmpName;
        $storagePath = 'profile_pictures/' . $uid;

        try {
            $storage = new \App\Libraries\StorageService();
            $photoUrl = $storage->upload($tmpPath, $storagePath, $mime);
        } catch (\Throwable $e) {
            @unlink($tmpPath);
            log_message('error', 'Profile::uploadPhoto Storage - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Failed to upload image.']);
        } finally {
            @unlink($tmpPath);
        }

        try {
            $this->firestore->getDb()->collection('users')->document($uid)->set(
                ['profile_photo_url' => $photoUrl, 'updated_at' => new \Google\Cloud\Core\Timestamp(new \DateTime())],
                ['merge' => true]
            );
        } catch (\Throwable $e) {
            log_message('error', 'Profile::uploadPhoto Firestore - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Uploaded image but failed to save URL.']);
        }

        // Cache URL in session so the header updates immediately
        session()->set('profilePicture', $photoUrl);

        return $this->response->setJSON([
            'success' => true,
            'url'     => $photoUrl,
        ]);
    }

    private static function resolveProfilePic(array $user): ?string
    {
        $url = (string)($user['profile_photo_url'] ?? '');
        if ($url !== '') return $url;

        $path = (string)($user['profile_picture'] ?? '');
        if ($path !== '' && file_exists(FCPATH . $path)) return $path;

        return null;
    }
}
