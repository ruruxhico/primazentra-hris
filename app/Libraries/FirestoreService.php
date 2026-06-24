<?php

namespace App\Libraries;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Cloud\Firestore\FirestoreClient;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Factory;

class FirestoreService {

    private static ?self $instance = null;

    protected FirestoreClient $db;
    protected Auth $auth;

    private function __construct() {
        $path = WRITEPATH . 'credentials/firebase_key.json';

        if (!file_exists($path)) {
            throw new \RuntimeException('Firebase key not found at: ' . $path);
        }

        try {
            $keyData = json_decode(file_get_contents($path), true);
            if (!is_array($keyData) || empty($keyData['project_id'])) {
                throw new \RuntimeException('Invalid firebase_key.json: missing project_id');
            }

            // Auth uses Kreait factory (handles credentials correctly for REST auth APIs)
            $factory    = (new Factory)->withServiceAccount($keyData);
            $this->auth = $factory->createAuth();

            // FirestoreClient requires credentials as a pre-built object via the 'credentials'
            // key — Kreait v7 passes wrong option names (credentialsFetcher/keyFile) to
            // google/gax which ignores them and falls back to ADC. Build credentials explicitly.
            $credentials = new ServiceAccountCredentials(
                FirestoreClient::FULL_CONTROL_SCOPE,
                $keyData
            );
            $this->db = new FirestoreClient([
                'projectId'   => $keyData['project_id'],
                'credentials' => $credentials,
            ]);
        } catch (\Exception $e) {
            throw new \RuntimeException('Firestore connection failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public function getDb(): FirestoreClient { return $this->db; }
    public function getAuth(): Auth { return $this->auth; }
}
