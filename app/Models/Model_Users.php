<?php

namespace App\Models;

class Model_Users extends Model_BaseFirestore 
{
    protected $collection = 'users';

    public function __construct() 
    {
        // This runs the Base Model code to set up $this->firestore
        parent::__construct();
    }

    public function getUserByEmail($email) 
    {
        $db = $this->firestore->getDb();
        // If the red error persists here, hover over 'getCollection' 
        // and check if it says "Method not found"
        $usersRef = $db->collection($this->collection);        
        $query = $usersRef->where('email', '=', $email)->limit(1);
        $documents = $query->documents();

        foreach ($documents as $document) {
            if ($document->exists()) {
                return [
                    'id'   => $document->id(),
                    'data' => $document->data()
                ];
            }
        }
        return null;
    }

    public function addStaffMember($data)
    {
        $db = $this->firestore->getDb();
        $usersRef = $db->collection($this->collection);
        return $usersRef->add($data);
    }

    /**
     * Count all staff members (excluding executives)
     */
    public function countStaff()
    {
        $db = $this->firestore->getDb();
        $usersRef = $db->collection($this->collection);
        // Get all users
        $documents = $usersRef->documents();
        
        $count = 0;
        foreach ($documents as $document) {
            if ($document->exists()) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get all users/staff
     */
    public function getAllStaff()
    {
        $db = $this->firestore->getDb();
        $usersRef = $db->collection($this->collection);
        $documents = $usersRef->documents();

        $staff = [];
        foreach ($documents as $document) {
            if ($document->exists()) {
                $data = $document->data();
                $data['id'] = $document->id();
                $staff[] = $data;
            }
        }
        return $staff;
    }

    /**
     * Get users by role.
     * Accepts a single role string or an array of aliases.
     */
    public function getUsersByRole($role)
    {
        $db = $this->firestore->getDb();
        $usersRef = $db->collection($this->collection);
        $roles = is_array($role) ? array_values(array_filter(array_map('strval', $role))) : [trim((string) $role)];

        $users = [];
        foreach (array_unique($roles) as $roleName) {
            if ($roleName === '') {
                continue;
            }

            $query = $usersRef->where('role', '=', $roleName)->documents();

            foreach ($query as $document) {
                if ($document->exists()) {
                    $data = $document->data();
                    $data['id'] = $document->id();
                    $users[] = $data;
                }
            }
        }

        return $users;
    }

    /**
     * Get a single user by document ID.
     */
    public function getUserById($userId)
    {
        if (!$userId) {
            return null;
        }

        $db = $this->firestore->getDb();
        $snapshot = $db->collection($this->collection)->document($userId)->snapshot();

        if ($snapshot->exists()) {
            $data = $snapshot->data();
            $data['id'] = $snapshot->id();
            return $data;
        }

        return null;
    }
}