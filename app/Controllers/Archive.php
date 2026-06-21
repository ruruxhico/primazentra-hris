<?php

namespace App\Controllers;

use App\Libraries\ArchiveService;
use App\Libraries\FirestoreService;

class Archive extends BaseController
{
    private ArchiveService $archive;

    public function __construct()
    {
        $this->archive = new ArchiveService(FirestoreService::getInstance());
    }

    public function restore()
    {
        $archiveId = trim((string)($this->request->getPost('arch_id') ?? ''));
        if (!$archiveId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing arch_id']);
        }
        return $this->response->setJSON($this->archive->restoreItem($archiveId));
    }

    public function permanentDelete()
    {
        $archiveId = trim((string)($this->request->getPost('arch_id') ?? ''));
        if (!$archiveId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing arch_id']);
        }
        $this->archive->permanentDelete($archiveId);
        return $this->response->setJSON(['success' => true]);
    }
}
