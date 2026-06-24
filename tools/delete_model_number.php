<?php
// Migration script: remove `model_number` field from all equipment/tools resource
// documents and their items subcollections.
// Usage: php tools/delete_model_number.php [--dry-run]

if (!defined('WRITEPATH')) {
    define('WRITEPATH', realpath(__DIR__ . '/../writable') . DIRECTORY_SEPARATOR);
}

require __DIR__ . '/../vendor/autoload.php';

use App\Libraries\FirestoreService;
use Google\Cloud\Firestore\FieldValue;

$dryRun = in_array('--dry-run', $argv, true);
echo "Starting delete_model_number (dry-run=" . ($dryRun ? 'true' : 'false') . ")\n";

$svc = new FirestoreService();
$db  = $svc->getDb();

$deleteField    = FieldValue::deleteField();
$docUpdated     = 0;
$itemsUpdated   = 0;

foreach ($db->collection('resource')->documents() as $doc) {
    if (!$doc->exists()) continue;
    $data     = $doc->data();
    $category = $data['category'] ?? '';
    if (!in_array($category, ['Equipment', 'Tools'], true)) continue;

    $docId = $doc->id();

    // Remove model_number from the parent resource document if present
    if (array_key_exists('model_number', $data) ||
        (isset($data['asset_details']) && array_key_exists('model_number', $data['asset_details']))) {

        echo "  Resource doc $docId ($category) — removing model_number\n";
        if (!$dryRun) {
            $updates = [];
            if (array_key_exists('model_number', $data)) {
                $updates[] = ['path' => 'model_number', 'value' => $deleteField];
            }
            if (isset($data['asset_details']) && array_key_exists('model_number', $data['asset_details'])) {
                $updates[] = ['path' => 'asset_details.model_number', 'value' => $deleteField];
            }
            $db->collection('resource')->document($docId)->update($updates);
        }
        $docUpdated++;
    }

    // Remove model_number from each unit in the items subcollection
    foreach ($db->collection('resource')->document($docId)->collection('items')->documents() as $itemDoc) {
        if (!$itemDoc->exists()) continue;
        $itemData = $itemDoc->data();
        if (!array_key_exists('model_number', $itemData)) continue;

        echo "    Item {$itemDoc->id()} — removing model_number\n";
        if (!$dryRun) {
            $itemDoc->reference()->update([
                ['path' => 'model_number', 'value' => $deleteField],
            ]);
        }
        $itemsUpdated++;
    }
}

echo "\nDone. Resource docs updated: $docUpdated | Items updated: $itemsUpdated\n";
if ($dryRun) echo "(Dry run — no changes written to Firestore)\n";
