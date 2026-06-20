<?php

namespace App\Controllers;

use App\Services\NotificationService;

class Notifications extends BaseController
{
    private NotificationService $svc;

    public function __construct()
    {
        $this->svc = new NotificationService();
    }

    // GET /notifications
    public function index(): \CodeIgniter\HTTP\ResponseInterface|string
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        return view('notifications/index', [
            'pageTitle' => 'Notifications',
            'userName'  => session()->get('userName') ?? 'User',
            'role'      => session()->get('role') ?? 'User',
        ]);
    }

    // GET /notifications/json
    public function json(): \CodeIgniter\HTTP\ResponseInterface
    {
        $uid = (string) session()->get('userId');
        if (!$uid || !session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }
        try {
            $items = $this->svc->getRecent($uid, 20);
            $unread = array_reduce($items, fn($c, $n) => $c + ($n['isRead'] ? 0 : 1), 0);
            return $this->response->setJSON(['notifications' => $items, 'unreadCount' => $unread]);
        } catch (\Throwable $e) {
            log_message('error', 'Notifications::json - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Server error']);
        }
    }

    // POST /notifications/mark-read
    public function markRead(): \CodeIgniter\HTTP\ResponseInterface
    {
        $uid = (string) session()->get('userId');
        if (!$uid || !session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }
        $id = (string)($this->request->getJSON(true)['id'] ?? '');
        if (!$id) return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing id']);
        try {
            $this->svc->markAsRead($uid, $id);
            return $this->response->setJSON(['ok' => true]);
        } catch (\Throwable $e) {
            log_message('error', 'Notifications::markRead - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Server error']);
        }
    }

    // POST /notifications/mark-all-read
    public function markAllRead(): \CodeIgniter\HTTP\ResponseInterface
    {
        $uid = (string) session()->get('userId');
        if (!$uid || !session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }
        try {
            $this->svc->markAllAsRead($uid);
            return $this->response->setJSON(['ok' => true]);
        } catch (\Throwable $e) {
            log_message('error', 'Notifications::markAllRead - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Server error']);
        }
    }
}
