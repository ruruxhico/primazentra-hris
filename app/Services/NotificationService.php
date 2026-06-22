<?php

namespace App\Services;

use App\Libraries\FirestoreService;
use Google\Cloud\Core\Timestamp;
use Google\Cloud\Firestore\FirestoreClient;

class NotificationService
{
    private FirestoreClient $db;

    public function __construct()
    {
        $this->db = (FirestoreService::getInstance())->getDb();
    }

    public function send(string $userId, string $type, string $title, string $body, array $metadata = []): string
    {
        $col = $this->db->collection('notifications')->document($userId)->collection('items');
        $ref = $col->newDocument();
        $ref->set([
            'id'        => $ref->id(),
            'userId'    => $userId,
            'type'      => $type,
            'title'     => $title,
            'body'      => $body,
            'isRead'    => false,
            'createdAt' => new Timestamp(new \DateTime()),
            'metadata'  => (object) $metadata,
        ]);
        return $ref->id();
    }

    public function markAsRead(string $userId, string $notifId): void
    {
        $this->db->collection('notifications')->document($userId)
            ->collection('items')->document($notifId)
            ->update([['path' => 'isRead', 'value' => true]]);
    }

    public function markAllAsRead(string $userId): void
    {
        $docs = $this->db->collection('notifications')->document($userId)
            ->collection('items')
            ->where('isRead', '=', false)
            ->documents();

        $batch = $this->db->batch();
        $count = 0;
        foreach ($docs as $doc) {
            if (!$doc->exists()) continue;
            $batch->update($doc->reference(), [['path' => 'isRead', 'value' => true]]);
            $count++;
        }
        if ($count > 0) $batch->commit();
    }

    public function getRecent(string $userId, int $limit = 20): array
    {
        $docs = $this->db->collection('notifications')->document($userId)
            ->collection('items')
            ->orderBy('createdAt', 'DESC')
            ->limit($limit)
            ->documents();

        $items = [];
        foreach ($docs as $doc) {
            if (!$doc->exists()) continue;
            $d = $doc->data();
            $items[] = [
                'id'        => $doc->id(),
                'type'      => (string)($d['type']    ?? ''),
                'title'     => (string)($d['title']   ?? ''),
                'body'      => (string)($d['body']    ?? ''),
                'isRead'    => (bool)($d['isRead']    ?? false),
                'createdAt' => $this->tsToIso($d['createdAt'] ?? null),
                'metadata'  => (array)($d['metadata'] ?? []),
            ];
        }
        return $items;
    }

    public function getUnreadCount(string $userId): int
    {
        $docs = $this->db->collection('notifications')->document($userId)
            ->collection('items')
            ->where('isRead', '=', false)
            ->documents();
        $n = 0;
        foreach ($docs as $doc) {
            if ($doc->exists()) $n++;
        }
        return $n;
    }

    private function tsToIso(mixed $value): ?string
    {
        if ($value === null) return null;
        if ($value instanceof Timestamp) {
            try {
                return \DateTimeImmutable::createFromInterface($value->get())
                    ->setTimezone(new \DateTimeZone('Asia/Manila'))
                    ->format('Y-m-d H:i:s');
            } catch (\Throwable) { return null; }
        }
        return null;
    }
}
