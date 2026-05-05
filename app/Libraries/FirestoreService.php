<?php

namespace App\Libraries;

// These 'use' statements are critical to stop the "Undefined type" errors
use Google\Cloud\Firestore\FirestoreClient;
use Kreait\Firebase\Factory;

class FirestoreService {
    protected $db;

    public function __construct() {
        // 1. Point exactly to the writable/credentials folder
        $path = WRITEPATH . 'credentials/firebase_key.json';

        // 2. CHECK: If it fails, let's see exactly WHERE it's looking
        if (!file_exists($path)) {
            $realPath = realpath(WRITEPATH) ?: WRITEPATH;
            die("<b>System Error:</b> Key not found.<br>
                 <b>PHP is looking here:</b> " . $realPath . "credentials\firebase_key.json<br>
                 <b>Action:</b> Please ensure the 'credentials' folder exists inside 'writable'.");
        }

        // 3. Set the environment variable for the Google SDK
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $path);

        try {
            // We use the Factory method which we already proved works in your test
            $factory = (new Factory)->withServiceAccount($path);
            $this->db = $factory->createFirestore()->database();
            
        } catch (\Exception $e) {
            die("<b>Firestore Connection Error:</b> " . $e->getMessage());
        }
    }

    public function getDb() {
        return $this->db;
    }
}