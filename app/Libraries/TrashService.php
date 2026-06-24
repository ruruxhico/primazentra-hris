<?php

namespace App\Libraries;

class TrashService
{
    private $db;

    public function __construct(FirestoreService $firestore)
    {
        $this->db = $firestore->getDb();
    }

    public function moveToTrash(
        string $module, string $originalId, string $name,
        array $data, array $subcollections = [], ?string $deletedBy = null
    ): string {
        $now       = new \Google\Cloud\Core\Timestamp(new \DateTime());
        $expiresAt = new \Google\Cloud\Core\Timestamp(new \DateTime('@' . (time() + 30 * 86400)));
        $ref = $this->db->collection('recently_deleted')->newDocument();
        $ref->set([
            'original_id'    => $originalId,
            'module'         => $module,
            'name'           => $name,
            'data'           => $data,
            'subcollections' => $subcollections,
            'deleted_at'     => $now,
            'deleted_by'     => $deletedBy ?? 'System',
            'expires_at'     => $expiresAt,
        ]);
        return $ref->id();
    }

    public function getTrashItems(string $module): array
    {
        $now = time();
        $items = [];
        foreach ($this->db->collection('recently_deleted')->documents() as $doc) {
            if (!$doc->exists()) continue;
            $d = $doc->data();
            if (($d['module'] ?? '') !== $module) continue;
            if ($this->toUnix($d['expires_at'] ?? 0) < $now) continue;
            $items[] = ['id' => $doc->id()] + $d;
        }
        usort($items, fn($a, $b) => $this->toUnix($b['deleted_at'] ?? 0) <=> $this->toUnix($a['deleted_at'] ?? 0));
        return $items;
    }

    private function toUnix(mixed $value): int
    {
        if ($value instanceof \Google\Cloud\Core\Timestamp) {
            return $value->get()->getTimestamp();
        }
        return (int) $value;
    }

    public function getTrashDoc(string $trashId): array
    {
        $snap = $this->db->collection('recently_deleted')->document($trashId)->snapshot();
        if (!$snap->exists()) return [];
        return ['id' => $trashId] + $snap->data();
    }

    public function restoreItem(string $trashId): array
    {
        try {
            $snap = $this->db->collection('recently_deleted')->document($trashId)->snapshot();
            if (!$snap->exists()) return ['success' => false, 'message' => 'Item not found in trash'];
            $d          = $snap->data();
            $module     = $d['module']      ?? '';
            $originalId = $d['original_id'] ?? '';
            $data       = $d['data']        ?? [];
            $subcols    = $d['subcollections'] ?? [];
            if (!$originalId) return ['success' => false, 'message' => 'Invalid trash record'];

            $colMap = ['project' => 'project', 'resource' => 'resource', 'manpower' => 'manpower', 'staff' => 'users'];
            $col = $colMap[$module] ?? $module;

            $this->db->collection($col)->document($originalId)->set($data);

            foreach ($subcols as $subName => $subDocs) {
                if (!is_array($subDocs)) continue;
                $subRef = $this->db->collection($col)->document($originalId)->collection($subName);
                foreach ($subDocs as $subDoc) {
                    $subId = $subDoc['__id'] ?? null;
                    unset($subDoc['__id']);
                    if ($subId) { $subRef->document($subId)->set($subDoc); }
                    else        { $subRef->newDocument()->set($subDoc); }
                }
            }

            $this->db->collection('recently_deleted')->document($trashId)->delete();
            return ['success' => true, 'module' => $module, 'name' => $d['name'] ?? '', 'id' => $originalId, 'data' => $data];
        } catch (\Throwable $e) {
            log_message('error', 'TrashService::restoreItem - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Restore failed'];
        }
    }

    public function permanentDelete(string $trashId): void
    {
        $this->db->collection('recently_deleted')->document($trashId)->delete();
    }
}
