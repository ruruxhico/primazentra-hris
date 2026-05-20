<?php
// Migration: move documents with category 'Manpower' from 'resource' collection to 'manpower' collection
require __DIR__ . '/../vendor/autoload.php';

use App\Libraries\FirestoreService;
use Google\Cloud\Core\Timestamp;

// CLI bootstrap for WRITEPATH if missing
if (!defined('WRITEPATH')) {
    define('WRITEPATH', realpath(__DIR__ . '/../writable') . DIRECTORY_SEPARATOR);
}

$dryRun = in_array('--dry-run', $argv, true);

echo "Starting manpower migration (dry-run=" . ($dryRun ? 'true' : 'false') . ")\n";

$svc = new FirestoreService();
$db = $svc->getDb();

$coll = $db->collection('resource');
$docs = $coll->documents();
$moved = 0;

foreach ($docs as $doc) {
    if (!$doc->exists()) continue;
    $id = $doc->id();
    $data = $doc->data();

    $category = isset($data['category']) ? (string)$data['category'] : '';
    if (strtolower($category) !== 'manpower') continue;

    // Normalize timestamps to Firestore Timestamp
    $created = $data['created_at'] ?? null;
    if (!($created instanceof Timestamp)) {
        if (is_numeric($created)) {
            $dt = new DateTime('@' . (int)$created);
            $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
            $data['created_at'] = new Timestamp($dt);
        } elseif ($created instanceof DateTimeInterface) {
            $data['created_at'] = new Timestamp($created);
        } else {
            $now = new Timestamp(new DateTime());
            $data['created_at'] = $now;
        }
    }

    $updated = $data['updated_at'] ?? null;
    if (!($updated instanceof Timestamp)) {
        if (is_numeric($updated)) {
            $dt = new DateTime('@' . (int)$updated);
            $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
            $data['updated_at'] = new Timestamp($dt);
        } elseif ($updated instanceof DateTimeInterface) {
            $data['updated_at'] = new Timestamp($updated);
        } else {
            $data['updated_at'] = $data['created_at'];
        }
    }

    if ($dryRun) {
        echo "Would move document {$id} to 'manpower' collection\n";
    } else {
        // If the document contains nested manpower_details, flatten into the new schema
        if (!empty($data['manpower_details']) && is_array($data['manpower_details'])) {
            $mp = $data['manpower_details'];
            $data['employee_id'] = $mp['manpower_id'] ?? ($data['employee_id'] ?? ($id));
            $data['first_name'] = $mp['first_name'] ?? ($data['first_name'] ?? '');
            if (!empty($mp['middle_name'])) $data['middle_name'] = $mp['middle_name'];
            $data['last_name'] = $mp['last_name'] ?? ($data['last_name'] ?? '');
            $data['status'] = $mp['status'] ?? ($data['status'] ?? 'Available');
            $data['role'] = $mp['role'] ?? ($data['role'] ?? '');
            $data['specialization'] = $mp['specialization'] ?? ($data['specialization'] ?? '');
            $data['contact_number'] = $mp['contact_number'] ?? ($data['contact_number'] ?? '');
            $data['assigned_project_id'] = $mp['assigned_project_id'] ?? ($data['assigned_project_id'] ?? null);
            if (!empty($mp['current_site_deployment']) && empty($data['location']['address'])) {
                $data['location']['address'] = $mp['current_site_deployment'];
            }

            // Remove nested payload to avoid duplication
            unset($data['manpower_details']);
        }

        // Write into manpower collection with same ID
        $db->collection('manpower')->document($id)->set($data);
        // Delete original document from resource collection
        $coll->document($id)->delete();
        echo "Moved document {$id}\n";
    }

    $moved++;
}

echo "Done. Documents moved: {$moved}\n";
if ($dryRun) echo "Dry-run: no writes performed.\n";

exit(0);
