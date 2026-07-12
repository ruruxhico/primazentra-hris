<?php

namespace App\Libraries;

class ArchiveService
{
    private $db;

    public function __construct(FirestoreService $firestore)
    {
        $this->db = $firestore->getDb();
    }

    public function moveToArchive(
        string $module, string $originalId, string $name,
        array $data, array $subcollections = [], ?string $archivedBy = null
    ): string {
        $ref = $this->db->collection('archived')->newDocument();
        $ref->set([
            'original_id'    => $originalId,
            'module'         => $module,
            'name'           => $name,
            'data'           => $data,
            'subcollections' => $subcollections,
            'archived_at'    => new \Google\Cloud\Core\Timestamp(new \DateTime()),
            'archived_by'    => $archivedBy ?? 'System',
        ]);
        audit_log("{$module}.archive", $module, $originalId, $name);
        return $ref->id();
    }

    public function getArchivedItems(string $module): array
    {
        $items = [];
        foreach ($this->db->collection('archived')->documents() as $doc) {
            if (!$doc->exists()) continue;
            $d = $doc->data();
            if (($d['module'] ?? '') !== $module) continue;
            $items[] = ['id' => $doc->id()] + $d;
        }
        usort($items, fn($a, $b) =>
            $this->toUnix($b['archived_at'] ?? 0) <=> $this->toUnix($a['archived_at'] ?? 0)
        );
        return $items;
    }

    private function toUnix(mixed $value): int
    {
        if ($value instanceof \Google\Cloud\Core\Timestamp) {
            return $value->get()->getTimestamp();
        }
        return (int) $value;
    }

    public function getArchivedDoc(string $archiveId): array
    {
        $snap = $this->db->collection('archived')->document($archiveId)->snapshot();
        if (!$snap->exists()) return [];
        return ['id' => $archiveId] + $snap->data();
    }

    public function restoreItem(string $archiveId): array
    {
        try {
            $snap = $this->db->collection('archived')->document($archiveId)->snapshot();
            if (!$snap->exists()) return ['success' => false, 'message' => 'Item not found in archive'];

            $d          = $snap->data();
            $module     = $d['module']         ?? '';
            $originalId = $d['original_id']    ?? '';
            $data       = $d['data']           ?? [];
            $subcols    = $d['subcollections'] ?? [];

            if (!$originalId) return ['success' => false, 'message' => 'Invalid archive record'];

            $colMap = ['project' => 'project', 'resource' => 'resource', 'manpower' => 'manpower', 'staff' => 'users', 'request' => 'resource_requests'];
            $col    = $colMap[$module] ?? $module;

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

            $this->db->collection('archived')->document($archiveId)->delete();
            audit_log("{$module}.restore", $module, $originalId, $d['name'] ?? '');
            return ['success' => true, 'module' => $module, 'name' => $d['name'] ?? '', 'id' => $originalId];
        } catch (\Throwable $e) {
            log_message('error', 'ArchiveService::restoreItem - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Restore failed'];
        }
    }

    public function permanentDelete(string $archiveId): void
    {
        $snap = $this->db->collection('archived')->document($archiveId)->snapshot();
        if ($snap->exists()) {
            $d = $snap->data();
            audit_log(($d['module'] ?? 'unknown') . '.permanent_delete', $d['module'] ?? 'unknown', $d['original_id'] ?? null, $d['name'] ?? '');
        }
        $this->db->collection('archived')->document($archiveId)->delete();
    }
}
