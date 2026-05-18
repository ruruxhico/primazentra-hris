<?php
// Migration script: move document-level `items` arrays into subcollections and normalize created_at
require __DIR__ . '/../vendor/autoload.php';

use App\Libraries\FirestoreService;
use Google\Cloud\Firestore\FieldValue;

// Simple CLI usage: php tools/migrate_resource_items.php [--dry-run]
$dryRun = in_array('--dry-run', $argv, true);

echo "Starting migration (dry-run=" . ($dryRun ? 'true' : 'false') . ")\n";

$svc = new FirestoreService();
$db = $svc->getDb();

$coll = $db->collection('resource');
$docs = $coll->documents();
$moved = 0;
$updated = 0;

foreach ($docs as $doc) {
    if (!$doc->exists()) continue;
    $id = $doc->id();
    $data = $doc->data();

    // Normalize created_at: ensure numeric epoch
    $created = $data['created_at'] ?? null;
    $needCreatedUpdate = false;
    if (empty($created) || !is_numeric($created)) {
        $data['created_at'] = time();
        $needCreatedUpdate = true;
    }

    // Migrate items array -> subcollection
    if (!empty($data['items']) && is_array($data['items'])) {
        echo "Document {$id}: found items array with " . count($data['items']) . " entries\n";
        $itemsRef = $coll->document($id)->collection('items');

        foreach ($data['items'] as $rawItem) {
            $item = is_array($rawItem) ? $rawItem : [];
            $itemId = $item['id'] ?? bin2hex(random_bytes(8));
            $item['id'] = $itemId;
            $item['created_at'] = $item['created_at'] ?? time();
            $item['updated_at'] = time();

            if ($dryRun) {
                echo "  would create item {$itemId}\n";
            } else {
                $itemsRef->document($itemId)->set($item);
                echo "  created item {$itemId}\n";
            }
            $moved++;
        }

        // Remove items array from document (or set to empty)
        if ($dryRun) {
            echo "  would remove items array from document {$id}\n";
            } else {
            try {
                // Replace items with an empty array to avoid leaving legacy data
                $coll->document($id)->set(['items' => []], ['merge' => true]);
                echo "  cleared items array from document {$id}\n";
            } catch (Exception $e) {
                echo "  failed to clear items array: " . $e->getMessage() . "\n";
            }
        }
        $updated++;
    }

    // Persist created_at fix if needed
    if ($needCreatedUpdate) {
        if ($dryRun) {
            echo "Document {$id}: would set created_at={$data['created_at']}\n";
        } else {
            $coll->document($id)->set(['created_at' => $data['created_at']], ['merge' => true]);
            echo "Document {$id}: set created_at={$data['created_at']}\n";
        }
        $updated++;
    }
}

echo "Done. Items moved: {$moved}. Documents updated: {$updated}.\n";

if ($dryRun) echo "Dry-run completed; no writes were performed.\n";

exit(0);
