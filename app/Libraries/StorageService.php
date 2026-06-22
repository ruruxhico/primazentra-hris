<?php

namespace App\Libraries;

use Kreait\Firebase\Factory;

class StorageService
{
    protected \Google\Cloud\Storage\Bucket $bucket;
    protected string $bucketName;

    public function __construct()
    {
        $this->bucketName = env('FIREBASE_STORAGE_BUCKET', 'nebuhive-primazentra.appspot.com');

        $path = WRITEPATH . 'credentials/firebase_key.json';

        if (file_exists($path)) {
            $serviceAccount = json_decode(file_get_contents($path), true);
            $factory = (new Factory)
                ->withServiceAccount($serviceAccount)
                ->withDefaultStorageBucket($this->bucketName);
        } else {
            $json = env('FIREBASE_CREDENTIALS', '{}');
            $credentials = json_decode($json, true);
            $factory = (new Factory)
                ->withServiceAccount($credentials)
                ->withDefaultStorageBucket($this->bucketName);
        }

        $this->bucket = $factory->createStorage()->getBucket();
    }

    /**
     * Upload a local file to Firebase Storage and return its permanent download URL.
     */
    public function upload(string $localPath, string $storagePath, string $mimeType): string
    {
        $token = $this->uuid();

        $this->bucket->upload(
            fopen($localPath, 'r'),
            [
                'name'     => $storagePath,
                'metadata' => [
                    'contentType' => $mimeType,
                    'metadata'    => [
                        'firebaseStorageDownloadTokens' => $token,
                    ],
                ],
            ]
        );

        return 'https://firebasestorage.googleapis.com/v0/b/'
            . urlencode($this->bucketName)
            . '/o/'
            . urlencode($storagePath)
            . '?alt=media&token=' . $token;
    }

    /**
     * Delete a file from Firebase Storage by its storage path.
     */
    public function delete(string $storagePath): void
    {
        try {
            $this->bucket->object($storagePath)->delete();
        } catch (\Throwable $e) {
            log_message('warning', 'StorageService::delete - ' . $e->getMessage());
        }
    }

    private function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
