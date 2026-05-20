<?php
// Migration script: move document-level `items` arrays into subcollections and convert legacy epoch timestamps to Firestore Timestamp objects.
if (!defined('WRITEPATH')) {
    define('WRITEPATH', realpath(__DIR__ . '/../writable') . DIRECTORY_SEPARATOR);
}

require __DIR__ . '/../vendor/autoload.php';

use App\Libraries\FirestoreService;
use Google\Cloud\Core\Timestamp;

// Simple CLI usage: php tools/migrate_resource_items.php [--dry-run]
$dryRun = in_array('--dry-run', $argv, true);

echo "Starting migration (dry-run=" . ($dryRun ? 'true' : 'false') . ")\n";

$svc = new FirestoreService();
$db = $svc->getDb();

$coll = $db->collection('resource');
$docs = $coll->documents();
$moved = 0;
$updated = 0;

function toFirestoreTimestamp($value): ?Timestamp
{
    if ($value instanceof Timestamp) {
        return $value;
    }

    if ($value instanceof DateTimeInterface) {
        return new Timestamp($value);
    }

    if (is_numeric($value)) {
        $dateTime = new DateTime('@' . (int) $value);
        $dateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));
        return new Timestamp($dateTime);
    }

    if (is_string($value) && trim($value) !== '') {
        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            $dateTime = new DateTime('@' . $timestamp);
            $dateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));
            return new Timestamp($dateTime);
        }
    }

    return null;
}

function describeTimestamp($value): string
{
    if ($value instanceof Timestamp) {
        try {
            return $value->get()->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return 'Timestamp';
        }
    }

    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d H:i:s');
    }

    if (is_numeric($value)) {
        return date('Y-m-d H:i:s', (int) $value);
    }

    return (string) $value;
}

foreach ($docs as $doc) {
    if (!$doc->exists()) continue;
    $id = $doc->id();
    $data = $doc->data();

    $docPatch = [];
    $createdAt = $data['created_at'] ?? null;
    $updatedAt = $data['updated_at'] ?? null;

    $createdTimestamp = toFirestoreTimestamp($createdAt);
    if ($createdTimestamp !== null && !($createdAt instanceof Timestamp)) {
        $docPatch['created_at'] = $createdTimestamp;
    }

    $updatedTimestamp = toFirestoreTimestamp($updatedAt);
    if ($updatedTimestamp !== null && !($updatedAt instanceof Timestamp)) {
        $docPatch['updated_at'] = $updatedTimestamp;
    } elseif ($updatedTimestamp === null && empty($updatedAt) && $createdTimestamp !== null) {
        $docPatch['updated_at'] = $createdTimestamp;
    }

    // Migrate items array -> subcollection
    if (!empty($data['items']) && is_array($data['items'])) {
        echo "Document {$id}: found items array with " . count($data['items']) . " entries\n";
        $itemsRef = $coll->document($id)->collection('items');

        foreach ($data['items'] as $rawItem) {
            $item = is_array($rawItem) ? $rawItem : [];
            $itemId = $item['id'] ?? bin2hex(random_bytes(8));
            $item['id'] = $itemId;

            $itemCreated = toFirestoreTimestamp($item['created_at'] ?? null);
            $itemUpdated = toFirestoreTimestamp($item['updated_at'] ?? null);

            if ($itemCreated !== null) {
                $item['created_at'] = $itemCreated;
            } elseif (empty($item['created_at'])) {
                $item['created_at'] = new Timestamp(new DateTime());
            }

            if ($itemUpdated !== null) {
                $item['updated_at'] = $itemUpdated;
            } else {
                $item['updated_at'] = new Timestamp(new DateTime());
            }

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

    // Persist top-level timestamp fixes if needed
    if (!empty($docPatch)) {
        if ($dryRun) {
            $pairs = [];
            foreach ($docPatch as $field => $value) {
                $pairs[] = $field . '=' . describeTimestamp($value);
            }
            echo "Document {$id}: would set " . implode(', ', $pairs) . "\n";
        } else {
            $coll->document($id)->set($docPatch, ['merge' => true]);
            $pairs = [];
            foreach ($docPatch as $field => $value) {
                $pairs[] = $field . '=' . describeTimestamp($value);
            }
            echo "Document {$id}: set " . implode(', ', $pairs) . "\n";
        }
        $updated++;
    }
}

echo "Done. Items moved: {$moved}. Documents updated: {$updated}.\n";

if ($dryRun) echo "Dry-run completed; no writes were performed.\n";

exit(0);
