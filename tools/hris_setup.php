<?php

/**
 * One-time setup for the three HRIS apps (see RUN-GUIDE.md).
 *
 *   php tools/hris_setup.php --key=C:\path\to\firebase_key.json [--force]
 *
 * Run from primazentra-hris. The other two apps must be its siblings
 * (htdocs/hris-attendance, htdocs/hris-payroll). The script:
 *   1. writes each app's .env from its .env.example, with six newly
 *      generated API keys that match across the apps;
 *   2. copies the Firebase service-account key into each app's
 *      writable/credentials/firebase_key.json.
 * An existing .env is left alone unless --force is given.
 */

$opts  = getopt('', ['key:', 'force']);
$force = isset($opts['force']);
$root  = dirname(__DIR__, 2);
$apps  = ['main' => 'primazentra-hris', 'attendance' => 'hris-attendance', 'payroll' => 'hris-payroll'];

foreach ($apps as $folder) {
    if (! is_file("{$root}/{$folder}/.env.example")) {
        fwrite(STDERR, "Missing {$root}/{$folder}/.env.example. Put the three app folders side by side in htdocs.\n");
        exit(1);
    }
}

// One key per caller -> provider pair.
$k = [];
foreach (array_keys($apps) as $caller) {
    foreach (array_keys($apps) as $provider) {
        if ($caller !== $provider) {
            $k["{$caller}>{$provider}"] = bin2hex(random_bytes(24));
        }
    }
}

$values = [
    'main' => [
        'HRIS_ATTENDANCE_API_KEY' => $k['main>attendance'],
        'HRIS_PAYROLL_API_KEY'    => $k['main>payroll'],
        'HRIS_API_KEYS'           => "attendance:{$k['attendance>main']},payroll:{$k['payroll>main']}",
    ],
    'attendance' => [
        'HRIS_MAIN_API_KEY'    => $k['attendance>main'],
        'HRIS_PAYROLL_API_KEY' => $k['attendance>payroll'],
        'HRIS_API_KEYS'        => "main:{$k['main>attendance']},payroll:{$k['payroll>attendance']}",
    ],
    'payroll' => [
        'HRIS_MAIN_API_KEY'       => $k['payroll>main'],
        'HRIS_ATTENDANCE_API_KEY' => $k['payroll>attendance'],
        'HRIS_API_KEYS'           => "main:{$k['main>payroll']},attendance:{$k['attendance>payroll']}",
    ],
];

$existing = array_filter($apps, static fn ($f) => is_file("{$root}/{$f}/.env"));
if ($existing !== [] && ! $force) {
    echo 'Kept existing .env in: ' . implode(', ', $existing) . " (use --force to regenerate all three; keys must match across apps).\n";
} else {
    foreach ($apps as $name => $folder) {
        $env = file_get_contents("{$root}/{$folder}/.env.example");
        foreach ($values[$name] as $var => $value) {
            $env = preg_replace('/^' . preg_quote($var, '/') . '\s*=.*$/m', "{$var} = {$value}", $env, 1, $count);
            if ($count !== 1) {
                fwrite(STDERR, "{$folder}/.env.example has no {$var} line.\n");
                exit(1);
            }
        }
        file_put_contents("{$root}/{$folder}/.env", $env);
        echo "Wrote {$folder}/.env\n";
    }
}

if (isset($opts['key'])) {
    $json = @file_get_contents($opts['key']);
    $key  = $json === false ? null : json_decode($json, true);
    if (! is_array($key) || empty($key['project_id']) || empty($key['private_key'])) {
        fwrite(STDERR, "{$opts['key']} is not a Firebase service-account key (needs project_id and private_key).\n");
        exit(1);
    }
    foreach ($apps as $folder) {
        $dir = "{$root}/{$folder}/writable/credentials";
        is_dir($dir) || mkdir($dir, 0700, true);
        copy($opts['key'], "{$dir}/firebase_key.json");
        echo "Copied Firebase key ({$key['project_id']}) to {$folder}/writable/credentials/\n";
    }
} else {
    echo "No --key given: copy the Firebase key to writable/credentials/firebase_key.json in each app yourself.\n";
}
