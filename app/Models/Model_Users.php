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
}