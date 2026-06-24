<?php

namespace App\Controllers;

use App\Libraries\ArchiveService;
use App\Libraries\FirestoreService;

class Archive extends BaseController
{
    private ArchiveService $archive;

    public function __construct()
    {
        if (!session()->get('isLoggedIn')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $this->archive = new ArchiveService(FirestoreService::getInstance());
    }

    public function restore()
    {
        $archiveId = trim((string)($this->request->getPost('arch_id') ?? ''));
        if (!$archiveId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing arch_id']);
        }
        $result = $this->archive->restoreItem($archiveId);
        $result['csrf_hash'] = csrf_hash();
        return $this->response->setJSON($result);
    }

    public function permanentDelete()
    {
        $archiveId = trim((string)($this->request->getPost('arch_id') ?? ''));
        if (!$archiveId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing arch_id']);
        }
        try {
            $this->archive->permanentDelete($archiveId);
            return $this->response->setJSON(['success' => true, 'csrf_hash' => csrf_hash()]);
        } catch (\Throwable $e) {
            log_message('error', 'Archive::permanentDelete - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Delete failed']);
        }
    }
}
