<?php

namespace App\Models;

class Model_Resources extends Model_BaseFirestore
{
    protected $collection = 'resource';

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
     * Get resource by ID
     */
    public function getResourceById($resourceId)
    {
        $db = $this->firestore->getDb();
        $document = $db->collection($this->collection)->document($resourceId)->snapshot();

        if ($document->exists()) {
            $data = $document->data();
            $data['id'] = $document->id();
            return $data;
        }
        return null;
    }

    /**
     * Create a new resource
     */
    public function createResource($data)
    {
        $db = $this->firestore->getDb();
        $data['created_at'] = time();
        $data['updated_at'] = time();
        
        $reference = $db->collection($this->collection)->add($data);
        return $reference->id();
    }

    /**
     * Update a resource
     */
    public function updateResource($resourceId, $data)
    {
        $db = $this->firestore->getDb();
        $db->collection($this->collection)->document($resourceId)->set($data);
        return true;
    }

    /**
     * Delete a resource
     */
    public function deleteResource($resourceId)
    {
        $db = $this->firestore->getDb();
        $db->collection($this->collection)->document($resourceId)->delete();
        return true;
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
     * Get equipment and tools (Equipment/Tools category)
     */
    public function getEquipment()
    {
        return $this->getByCategory('Equipment');
    }

    /**
     * Get tools only (Tools category)
     */
    public function getTools()
    {
        return $this->getByCategory('Tools');
    }

    /**
     * Get manpower resources
     */
    public function getManpower()
    {
        return $this->getByCategory('Manpower');
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

    /**
     * Get resource items (subcollection)
     */
    public function getResourceItems($resourceId)
    {
        $db = $this->firestore->getDb();
        $documents = $db->collection($this->collection)
                        ->document($resourceId)
                        ->collection('items')
                        ->documents();

        $items = [];
        foreach ($documents as $document) {
            if ($document->exists()) {
                $data = $document->data();
                $data['id'] = $document->id();
                $items[] = $data;
            }
        }
        return $items;
    }

    /**
     * Create resource item
     */
    public function createResourceItem($resourceId, $itemData)
    {
        $db = $this->firestore->getDb();
        $itemData['created_at'] = time();
        $itemData['updated_at'] = time();
        $reference = $db->collection($this->collection)
                        ->document($resourceId)
                        ->collection('items')
                        ->add($itemData);
        return $reference->id();
    }
}
