<?php

namespace App\Services;

use App\Libraries\FirestoreService;
use App\Models\Model_Users;
use Google\Cloud\Core\Timestamp;

class ChatService
{
    private \Google\Cloud\Firestore\FirestoreClient $db;

    public function __construct()
    {
        $this->db = FirestoreService::getInstance()->getDb();
    }

    /**
     * Return the chat ID for a project, creating the group chat if it doesn't exist yet.
     * Pass $projectData to skip the Firestore re-read when the caller already has it.
     */
    public function createOrGetProjectChat(string $projectId, ?array $projectData = null): string
    {
        $existing = $this->db->collection('chats')
            ->where('project_id', '=', $projectId)
            ->limit(1)
            ->documents();

        foreach ($existing as $doc) {
            if ($doc->exists()) return $doc->id();
        }

        if ($projectData === null) {
            $pSnap = $this->db->collection('project')->document($projectId)->snapshot();
            if (!$pSnap->exists()) {
                throw new \RuntimeException("Project {$projectId} not found");
            }
            $projectData = $pSnap->data();
        }

        $projectName = (string)($projectData['project_name'] ?? $projectId);
        ['ids' => $memberIds, 'names' => $memberNames] = $this->computeMembers($projectData);

        $ref = $this->db->collection('chats')->add([
            'project_id'   => $projectId,
            'project_name' => $projectName,
            'type'         => 'group',
            'members'      => $memberIds,
            'member_names' => $memberNames,
            'created_at'   => new Timestamp(new \DateTime()),
            'last_message' => null,
            'unread_counts' => (object)[],
        ]);

        return $ref->id();
    }

    /**
     * Compute member UIDs and full-name map for a project GC:
     * all Administrators + Operations Managers + the project PM + SE.
     */
    public function computeMembers(array $pd): array
    {
        $ids    = [];
        $names  = [];
        $uModel = new Model_Users();

        foreach ($uModel->getUsersByRole(['Administrator', 'Operations Manager']) as $u) {
            $uid = (string)($u['id'] ?? '');
            if (!$uid || in_array($uid, $ids, true)) continue;
            $ids[]       = $uid;
            $names[$uid] = $this->buildFullName($u);
        }

        $pmRaw = $pd['personnel']['project_manager_id'] ?? $pd['project_manager_id'] ?? null;
        $pmUid = $this->extractUid($pmRaw);
        if ($pmUid && !in_array($pmUid, $ids, true)) {
            $ids[]        = $pmUid;
            $u            = $uModel->getUserById($pmUid);
            $names[$pmUid] = $u ? $this->buildFullName($u['data']) : $pmUid;
        }

        $seRaw = $pd['personnel']['site_engineer_id'] ?? $pd['site_engineer_id'] ?? null;
        $seUid = $this->extractUid($seRaw);
        if ($seUid && !in_array($seUid, $ids, true)) {
            $ids[]        = $seUid;
            $u            = $uModel->getUserById($seUid);
            $names[$seUid] = $u ? $this->buildFullName($u['data']) : $seUid;
        }

        return ['ids' => $ids, 'names' => $names];
    }

    public function buildFullName(array $u): string
    {
        $fn   = trim((string)($u['first_name'] ?? ''));
        $ln   = trim((string)($u['last_name']  ?? ''));
        $full = trim("$fn $ln");
        return $full ?: trim((string)($u['email'] ?? '')) ?: 'Unknown';
    }

    public function extractUid(mixed $value): string
    {
        if ($value === null) return '';
        if ($value instanceof \Google\Cloud\Firestore\DocumentReference) return $value->id();
        if (is_string($value)) {
            $last = ltrim(strrchr($value, '/'), '/');
            return $last ?: $value;
        }
        return '';
    }
}
