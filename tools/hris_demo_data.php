<?php

/**
 * Saves or restores the HRIS demo data (see RUN-GUIDE.md).
 *
 *   php tools/hris_demo_data.php export            writes tools/hris_demo_data.json
 *   php tools/hris_demo_data.php restore --yes     puts Firestore back to that file
 *
 * Run from primazentra-hris; uses writable/credentials/firebase_key.json.
 * Only the HRIS collections below are touched. Restore deletes every
 * document in them that is not in the file, so the run starts from a
 * known state (employees, DTR, payroll runs, period locks).
 */

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Cloud\Core\Timestamp;
use Google\Cloud\Firestore\FirestoreClient;

require dirname(__DIR__) . '/vendor/autoload.php';

const COLLECTIONS = ['employees', 'dtr_records', 'period_locks', 'payroll_runs', 'payroll_details'];
const FIXTURE     = __DIR__ . '/hris_demo_data.json';

$mode = $argv[1] ?? '';
if (! in_array($mode, ['export', 'restore'], true)) {
    fwrite(STDERR, "Usage: php tools/hris_demo_data.php export | restore --yes\n");
    exit(1);
}

$keyFile = dirname(__DIR__) . '/writable/credentials/firebase_key.json';
$key     = is_file($keyFile) ? json_decode((string) file_get_contents($keyFile), true) : null;
if (! is_array($key) || empty($key['project_id'])) {
    fwrite(STDERR, "No valid Firebase key at {$keyFile}.\n");
    exit(1);
}
$db = new FirestoreClient([
    'projectId'   => $key['project_id'],
    'credentials' => new ServiceAccountCredentials(FirestoreClient::FULL_CONTROL_SCOPE, $key),
]);

/** Firestore value -> JSON-safe value (timestamps become {"__ts": ISO-8601}). */
function toJson(mixed $v): mixed
{
    if ($v instanceof Timestamp) {
        return ['__ts' => $v->formatAsString()];
    }
    if (is_array($v)) {
        return array_map('toJson', $v);
    }
    if (is_object($v)) {
        throw new RuntimeException('Unsupported Firestore value type: ' . $v::class);
    }

    return $v;
}

function fromJson(mixed $v): mixed
{
    if (is_array($v) && array_keys($v) === ['__ts']) {
        return new Timestamp(new DateTimeImmutable($v['__ts']));
    }

    return is_array($v) ? array_map('fromJson', $v) : $v;
}

if ($mode === 'export') {
    $out = ['exported_at' => date(DATE_ATOM), 'collections' => []];
    foreach (COLLECTIONS as $c) {
        $docs = [];
        foreach ($db->collection($c)->documents() as $doc) {
            if ($doc->exists()) {
                $docs[$doc->id()] = toJson($doc->data());
            }
        }
        ksort($docs);
        $out['collections'][$c] = $docs;
        echo str_pad($c, 16) . count($docs) . " document(s)\n";
    }
    file_put_contents(FIXTURE, json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    echo 'Saved ' . FIXTURE . "\n";
    exit(0);
}

if (! in_array('--yes', $argv, true)) {
    fwrite(STDERR, "This replaces all HRIS data in Firebase project \"{$key['project_id']}\" with " . basename(FIXTURE) . ". Re-run with --yes.\n");
    exit(1);
}
$fixture = json_decode((string) @file_get_contents(FIXTURE), true);
if (! is_array($fixture['collections'] ?? null)) {
    fwrite(STDERR, 'Missing or invalid ' . FIXTURE . "\n");
    exit(1);
}

foreach (COLLECTIONS as $c) {
    $want    = $fixture['collections'][$c] ?? [];
    $deleted = 0;
    foreach ($db->collection($c)->documents() as $doc) {
        if ($doc->exists() && ! array_key_exists($doc->id(), $want)) {
            $doc->reference()->delete();
            $deleted++;
        }
    }
    foreach ($want as $id => $data) {
        $db->collection($c)->document((string) $id)->set(fromJson($data));
    }
    echo str_pad($c, 16) . count($want) . " restored, {$deleted} removed\n";
}
echo "Done. Firebase project: {$key['project_id']}\n";
