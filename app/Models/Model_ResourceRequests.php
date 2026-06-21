<?php

namespace App\Models;

use Google\Cloud\Core\Timestamp;

class Model_ResourceRequests extends Model_BaseFirestore
{
    protected $collection = 'resource_requests';

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(array $filters = []): array
    {
        $db   = $this->firestore->getDb();
        $docs = $db->collection($this->collection)
                   ->orderBy('created_at', 'DESCENDING')
                   ->documents();

        $requests = [];
        foreach ($docs as $doc) {
            if (!$doc->exists()) continue;
            $data         = $doc->data();
            $data['id']   = $doc->id();
            $data['created_at_fmt'] = $this->fmtTs($data['created_at'] ?? null);
            $requests[] = $data;
        }

        if (!empty($filters['status'])) {
            $s = strtolower($filters['status']);
            $requests = array_values(array_filter($requests, fn($r) => strtolower($r['status'] ?? '') === $s));
        }
        if (!empty($filters['category'])) {
            $c = strtolower($filters['category']);
            $requests = array_values(array_filter($requests, fn($r) => strtolower($r['category'] ?? '') === $c));
        }
        if (!empty($filters['search'])) {
            $q = strtolower($filters['search']);
            $requests = array_values(array_filter($requests, function ($r) use ($q) {
                return str_contains(strtolower($r['id'] ?? ''), $q)
                    || str_contains(strtolower($r['source_site_name'] ?? ''), $q)
                    || str_contains(strtolower($r['destination_site_name'] ?? ''), $q)
                    || str_contains(strtolower($r['resource_name'] ?? ''), $q)
                    || str_contains(strtolower($r['requested_by_name'] ?? ''), $q);
            }));
        }

        return $requests;
    }

    public function getById(string $id): ?array
    {
        $db  = $this->firestore->getDb();
        $doc = $db->collection($this->collection)->document($id)->snapshot();
        if (!$doc->exists()) return null;
        $data = $doc->data();
        $data['id'] = $doc->id();
        $data['created_at_fmt']           = $this->fmtTs($data['created_at'] ?? null);
        $data['approved_at_fmt']           = $this->fmtTs($data['approved_at'] ?? null);
        $data['secondary_approved_at_fmt'] = $this->fmtTs($data['secondary_approved_at'] ?? null);
        return $data;
    }

    public function create(array $data): string
    {
        $db  = $this->firestore->getDb();
        $now = new Timestamp(new \DateTime());
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $data['status']     = 'Pending';
        $data['reserved_resources'] = [];
        $ref = $db->collection($this->collection)->add($data);
        return $ref->id();
    }

    public function update(string $id, array $data): bool
    {
        $db = $this->firestore->getDb();
        $data['updated_at'] = new Timestamp(new \DateTime());
        $db->collection($this->collection)->document($id)->set($data, ['merge' => true]);
        return true;
    }

    private function fmtTs($ts): string
    {
        if (!$ts) return '';
        if ($ts instanceof Timestamp) return $ts->get()->format('m/d/Y H:i');
        return '';
    }
}
