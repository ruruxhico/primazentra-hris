<?php
// One-time cleanup script: remove legacy password_hash fields from Firestore user documents.

if (!defined('WRITEPATH')) {
    define('WRITEPATH', realpath(__DIR__ . '/../writable') . DIRECTORY_SEPARATOR);
}

require __DIR__ . '/../vendor/autoload.php';

use App\Libraries\FirestoreService;
use Google\Cloud\Firestore\FieldValue;

$dryRun = in_array('--dry-run', $argv, true);

echo "Starting legacy password hash cleanup (dry-run=" . ($dryRun ? 'true' : 'false') . ")\n";

$svc = new FirestoreService();
$db = $svc->getDb();
$usersRef = $db->collection('users');
$documents = $usersRef->documents();

$scanned = 0;
$updated = 0;

foreach ($documents as $document) {
    if (! $document->exists()) {
        continue;
    }

    $scanned++;
    $data = $document->data();
    $userId = $document->id();

    if (! array_key_exists('password_hash', $data)) {
        continue;
    }

    $currentValue = $data['password_hash'];
    echo "Found legacy password_hash on user {$userId}";

    if ($currentValue !== null && $currentValue !== '') {
        echo " (value present)";
    }

    echo "\n";

    if ($dryRun) {
        echo "  would remove password_hash from {$userId}\n";
        $updated++;
        continue;
    }

    $usersRef->document($userId)->set([
        'password_hash' => FieldValue::deleteField(),
    ], ['merge' => true]);

    echo "  removed password_hash from {$userId}\n";
    $updated++;
}

echo "Done. Documents scanned: {$scanned}. Documents updated: {$updated}.\n";

if ($dryRun) {
    echo "Dry-run completed; no writes were performed.\n";
}

exit(0);
