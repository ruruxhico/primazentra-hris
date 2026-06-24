<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;
use App\Libraries\TrashService;

class Trash extends BaseController
{
    private TrashService $trash;
    private FirestoreService $firestore;

    public function __construct()
    {
        if (!session()->get('isLoggedIn')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $this->firestore = FirestoreService::getInstance();
        $this->trash     = new TrashService($this->firestore);
    }

    public function restore()
    {
        $trashId = trim((string) ($this->request->getPost('trash_id') ?? ''));
        if (!$trashId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing trash_id']);
        }
        $result = $this->trash->restoreItem($trashId);
        $result['csrf_hash'] = csrf_hash();
        return $this->response->setJSON($result);
    }

    public function permanentDelete()
    {
        $trashId = trim((string) ($this->request->getPost('trash_id') ?? ''));
        if (!$trashId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing trash_id']);
        }

        // For staff: delete Firebase Auth account on permanent delete (kept during soft-delete)
        try {
            $doc = $this->trash->getTrashDoc($trashId);
            if (($doc['module'] ?? '') === 'staff' && !empty($doc['original_id'])) {
                try { $this->firestore->getAuth()->deleteUser($doc['original_id']); }
                catch (\Throwable $e) { log_message('warning', 'Trash::permanentDelete Auth: ' . $e->getMessage()); }
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Trash::permanentDelete read: ' . $e->getMessage());
        }

        try {
            $this->trash->permanentDelete($trashId);
            return $this->response->setJSON(['success' => true, 'csrf_hash' => csrf_hash()]);
        } catch (\Throwable $e) {
            log_message('error', 'Trash::permanentDelete - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Delete failed']);
        }
    }
}
