<?php

namespace App\Controllers;

// Use the Factory from the vendor folder
use Kreait\Firebase\Factory;

class FirebaseTest extends BaseController
{
    public function index()
    {
        // 1. Define the filename
        $fileName = 'firebase_key.json';
        
        // 2. Path verified: writable/credentials/
        $path = WRITEPATH . 'credentials/' . $fileName;

        echo "<h2>PrimaZentra Diagnostic Tool (REST Mode)</h2>";
        echo "<b>Checking Path:</b> " . $path . "<br>";

        // 3. Physical File Check
        if (file_exists($path)) {
            echo "<span style='color:green'>✔ File exists in the folder!</span><br>";
        } else {
            echo "<span style='color:red'>✘ File NOT found!</span><br>";
            echo "<i>Path expected: " . $path . "</i>";
            return; 
        }

        try {
            // 1. Set the credentials path
            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $path);

            /** 
             * THE MAGIC FIX:
             * This forces the library to use standard HTTP (REST) instead of gRPC.
             * This prevents the ERR_CONNECTION_RESET crash in PHP 8.2.
             */
            putenv('GOOGLE_CLOUD_FIRESTORE_TRANSPORT=rest');

            // 2. Initialize Firestore
            $factory = (new Factory)->withServiceAccount($path);
            $firestore = $factory->createFirestore();
            $db = $firestore->database();

            // 3. THE REAL TEST: Write data using REST protocol
            $docRef = $db->collection('system_test')->document('ping');
            $docRef->set([
                'status' => 'Online (via REST)!',
                'framework' => 'CodeIgniter 4',
                'project' => 'PrimaZentra',
                'updated_by' => 'Ayel',
                'time' => date('Y-m-d H:i:s')
            ]);

            echo "<span style='color:green'>✔ Firebase Credentials Accepted!</span><br>";
            echo "<span style='color:green'>✔ REST Transport Active!</span><br>";
            echo "<h3>🔥 Connection 100% Successful!</h3>";
            echo "<p>Data sent to Firestore using standard HTTP/REST fallback.</p>";
            echo "<p>Check your Firebase Console -> <b>system_test</b> collection.</p>";

        } catch (\Exception $e) {
            echo "<span style='color:red'>✘ Write Failed:</span> " . $e->getMessage();
            echo "<br><br><b>Troubleshooting:</b> If you get a 'cURL error 60', you need to apply the <i>cacert.pem</i> fix in your php.ini.";
        }
    }
}