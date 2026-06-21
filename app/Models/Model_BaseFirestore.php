<?php

namespace App\Models;

use App\Libraries\FirestoreService;

class Model_BaseFirestore 
{
    
    protected $firestore;

    public function __construct() 
    {
        // One place to initialize the service
        $this->firestore = FirestoreService::getInstance();
    }
    public function getFirestoreInstance() {
    return $this->firestore;
}
}