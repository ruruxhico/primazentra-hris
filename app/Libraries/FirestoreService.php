<?php

namespace App\Libraries;

use Google\Cloud\Firestore\FirestoreClient;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Factory;

class FirestoreService {
    protected FirestoreClient $db;
    protected Auth $auth;

    public function __construct() {
        $path = WRITEPATH . 'credentials/firebase_key.json';

        if (!file_exists($path)) {
            die("<b>System Error:</b> Firebase key not found at: " . htmlspecialchars($path));
        }

        // Set env var so gRPC credential chain finds the file
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . realpath($path));

        try {
            $factory      = (new Factory)->withServiceAccount($path);
            $this->db     = $factory->createFirestore()->database();
            $this->auth   = $factory->createAuth();

        } catch (\Exception $e) {
            die("<b>Firestore Connection Error:</b> " . htmlspecialchars($e->getMessage()));
        }
    }

    public function getDb(): FirestoreClient { return $this->db; }
    public function getAuth(): Auth { return $this->auth; }
}
