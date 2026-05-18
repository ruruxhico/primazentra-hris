<?php
// Reverse migration: move items from subcollection back into document 'items' array
require __DIR__ . '/../vendor/autoload.php';

use App\Libraries\FirestoreService;

// Usage: php tools/reverse_migrate_resource_items.php [--dry-run] [--delete-subcollection]
$dryRun = in_array('--dry-run', $argv, true);
$deleteSub = in_array('--delete-subcollection', $argv, true);

echo "Reverse migration starting (dry-run=" . ($dryRun ? 'true' : 'false') . ", delete-subcollection=" . ($deleteSub ? 'true' : 'false') . ")\n";

$svc = new FirestoreService();
$db = $svc->getDb();

$coll = $db->collection('resource');
$docs = $coll->documents();
$restored = 0;
$deleted = 0;

foreach ($docs as $doc) {
    if (!$doc->exists()) continue;
    $id = $doc->id();
    $data = $doc->data();

    $itemsRef = $coll->document($id)->collection('items');
    $items = [];
    $itemIds = [];
    foreach ($itemsRef->documents() as $itemDoc) {
        if (!$itemDoc->exists()) {
            continue;
        }
        $items[] = $itemDoc->data();
        $itemIds[] = $itemDoc->id();
    }

    if (empty($items)) {
        continue;
    }

    echo "Document {$id}: will restore " . count($items) . " items to document.items\n";

    if (!$dryRun) {
        try {
            $coll->document($id)->set(['items' => $items], ['merge' => true]);
            $restored++;
        } catch (Exception $e) {
            echo "  failed to write items for {$id}: " . $e->getMessage() . "\n";
            continue;
        }
    }

    if ($deleteSub && !$dryRun) {
        foreach ($itemIds as $itemId) {
            try {
                $itemsRef->document($itemId)->delete();
                $deleted++;
            } catch (Exception $e) {
                echo "  failed to delete item {$itemId}: " . $e->getMessage() . "\n";
            }
        }
        echo "  deleted " . count($itemIds) . " subcollection docs for {$id}\n";
    }
}

echo "Reverse migration completed. Documents restored: {$restored}. Subcollection documents deleted: {$deleted}.\n";

if ($dryRun) echo "Dry-run: no writes performed.\n";

exit(0);
