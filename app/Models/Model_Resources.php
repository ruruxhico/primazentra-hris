<?php

namespace App\Models;

class Model_Resources extends Model_BaseFirestore
{
    protected $collection = 'resources';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all resources
     */
    public function getAllResources()
    {
        $db = $this->firestore->getDb();
        $collection = $db->collection($this->collection);
        $documents = $collection->documents();

        $resources = [];
        foreach ($documents as $document) {
            if ($document->exists()) {
                $data = $document->data();
                $data['id'] = $document->id();
                $resources[] = $data;
            }
        }
        return $resources;
    }

    /**
     * Get resources by category
     */
    public function getByCategory($category)
    {
        $db = $this->firestore->getDb();
        $collection = $db->collection($this->collection);
        $query = $collection->where('category', '=', $category);
        $documents = $query->documents();

        $resources = [];
        foreach ($documents as $document) {
            if ($document->exists()) {
                $data = $document->data();
                $data['id'] = $document->id();
                $resources[] = $data;
            }
        }
        return $resources;
    }

    /**
     * Get available resources
     */
    public function getAvailableResources()
    {
        $db = $this->firestore->getDb();
        $collection = $db->collection($this->collection);
        $query = $collection->where('status', '=', 'Available');
        $documents = $query->documents();

        $resources = [];
        foreach ($documents as $document) {
            if ($document->exists()) {
                $data = $document->data();
                $data['id'] = $document->id();
                $resources[] = $data;
            }
        }
        return $resources;
    }

    /**
     * Get resources by status
     */
    public function getByStatus($status)
    {
        $db = $this->firestore->getDb();
        $collection = $db->collection($this->collection);
        $query = $collection->where('status', '=', $status);
        $documents = $query->documents();

        $resources = [];
        foreach ($documents as $document) {
            if ($document->exists()) {
                $data = $document->data();
                $data['id'] = $document->id();
                $resources[] = $data;
            }
        }
        return $resources;
    }

    /**
     * Get tools only (Equipment category)
     */
    public function getTools()
    {
        return $this->getByCategory('Equipment');
    }

    /**
     * Count total resources
     */
    public function countResources()
    {
        return count($this->getAllResources());
    }

    /**
     * Get deployed resources
     */
    public function getDeployedResources()
    {
        return $this->getByStatus('Deployed');
    }
}
