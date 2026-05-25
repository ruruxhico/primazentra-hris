<?php
require __DIR__ . '/../vendor/autoload.php';

$serviceAccount = __DIR__ . '/../writable/credentials/firebase_key.json';
if (! file_exists($serviceAccount)) {
    fwrite(STDERR, "Service account not found at: $serviceAccount\n");
    exit(2);
}

putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $serviceAccount);

$email = $argv[1] ?? getenv('EMAIL_SMTP_USER') ?: '';
if ($email === '') {
    fwrite(STDERR, "Usage: php test_generate_reset.php email@example.com\n");
    exit(2);
}

$actionCodeSettings = [
    'continueUrl' => rtrim('http://localhost/primazentra/reset-password', '/'),
    'handleCodeInApp' => true,
];

try {
    $factory = (new \Kreait\Firebase\Factory())->withServiceAccount($serviceAccount);
    $auth = $factory->createAuth();
    $link = $auth->getPasswordResetLink($email, $actionCodeSettings);
    echo "Reset link generated:\n" . $link . "\n";
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, "Error generating reset link: " . $e->getMessage() . "\n");
    exit(1);
}
