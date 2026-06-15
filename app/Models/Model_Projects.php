<?php

namespace App\Models;

class Model_Projects extends Model_BaseFirestore
{
    protected $collection = 'project';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all projects
     */
    public function getAllProjects()
    {
        $db = $this->firestore->getDb();
        $collection = $db->collection($this->collection);
        $documents = $collection->documents();

        $projects = [];
        foreach ($documents as $document) {
            if ($document->exists()) {
                $data = $document->data();
                $data['id'] = $document->id();
                $projects[] = $data;
            }
        }
        return $projects;
    }

    /**
     * Get projects by status
     */
    public function getByStatus($status)
    {
        $db = $this->firestore->getDb();
        $collection = $db->collection($this->collection);
        $query = $collection->where('status', '=', $status);
        $documents = $query->documents();

        $projects = [];
        foreach ($documents as $document) {
            if ($document->exists()) {
                $data = $document->data();
                $data['id'] = $document->id();
                $projects[] = $data;
            }
        }
        return $projects;
    }

    /**
     * Get monitorable projects: ON-GOING, PLANNED, and COMPLETED (excludes HALTED)
     */
    public function getActiveProjects()
    {
        $db = $this->firestore->getDb();
        $collection = $db->collection($this->collection);
        $query = $collection->where('status', 'in', ['On-going', 'Planned', 'ON-GOING', 'PLANNED', 'COMPLETED', 'Completed']);
        $documents = $query->documents();

        $projects = [];
        foreach ($documents as $document) {
            if ($document->exists()) {
                $data = $document->data();
                $data['id'] = $document->id();
                $projects[] = $data;
            }
        }
        return $projects;
    }

    /**
     * Get completed projects
     */
    public function getCompletedProjects()
    {
        return $this->getByStatus('Completed');
    }

    /**
     * Get halted projects
     */
    public function getHaltedProjects()
    {
        return $this->getByStatus('Halted');
    }

    /**
     * Get projects with issues (potential blockers)
     */
    public function getProjectsWithBlockers()
    {
        $db = $this->firestore->getDb();
        $collection = $db->collection($this->collection);
        
        // Get all active projects
        $projects = $this->getActiveProjects();
        
        // Filter projects that have low completion or are halted
        $blockers = [];
        foreach ($projects as $project) {
            $completion = $project['completion_percent'] ?? 0;
            
            // Consider as blocker if completion is low compared to time elapsed
            if ($completion < 30 || $project['status'] === 'Halted') {
                $blockers[] = $project;
            }
        }
        
        return $blockers;
    }

    /**
     * Count total projects
     */
    public function countProjects()
    {
        return count($this->getAllProjects());
    }

    /**
     * Count active projects
     */
    public function countActiveProjects()
    {
        return count($this->getActiveProjects());
    }

    /**
     * Get project by ID
     */
    public function getProjectById($projectId)
    {
        $db = $this->firestore->getDb();
        $doc = $db->collection($this->collection)->document($projectId)->snapshot();

        if ($doc->exists()) {
            $data = $doc->data();
            $data['id'] = $doc->id();
            return $data;
        }
        return null;
    }
}
