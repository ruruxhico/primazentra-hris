<?php

namespace App\Models;

use Google\Cloud\Core\Timestamp;

class Model_Manpower extends Model_BaseFirestore
{
    protected $collection = 'manpower';

    public function __construct()
    {
        parent::__construct();
    }

    public function getAllManpower()
    {
        $db = $this->firestore->getDb();
        $collection = $db->collection($this->collection);
        $documents = $collection->documents();

        $items = [];
        foreach ($documents as $doc) {
            if ($doc->exists()) {
                $data = $doc->data();
                $data['id'] = $doc->id();
                $items[] = $data;
            }
        }
        return $items;
    }

    public function getManpowerById($id)
    {
        $db = $this->firestore->getDb();
        $snapshot = $db->collection($this->collection)->document($id)->snapshot();
        if ($snapshot->exists()) {
            $data = $snapshot->data();
            $data['id'] = $snapshot->id();
            return $data;
        }
        return null;
    }

    public function createManpower($data)
    {
        $db = $this->firestore->getDb();
        $now = new Timestamp(new \DateTime());
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $ref = $db->collection($this->collection)->add($data);
        return $ref->id();
    }

    public function updateManpower($id, $data)
    {
        $db = $this->firestore->getDb();
        $db->collection($this->collection)->document($id)->set($data, ['merge' => true]);
        return true;
    }

    public function deleteManpower($id)
    {
        $db = $this->firestore->getDb();
        $db->collection($this->collection)->document($id)->delete();
        return true;
    }
}
