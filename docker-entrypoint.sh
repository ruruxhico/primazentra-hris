#!/bin/bash
set -e

# Decode Firebase credentials from Railway environment variable and write to disk.
# Your FirestoreService.php expects the file at writable/credentials/firebase_key.json.
# On Railway, set FIREBASE_CREDENTIALS_BASE64 to the base64-encoded firebase_key.json content.
if [ -n "$FIREBASE_CREDENTIALS_BASE64" ]; then
    echo "$FIREBASE_CREDENTIALS_BASE64" | base64 -d \
        > /var/www/html/writable/credentials/firebase_key.json
    chown www-data:www-data /var/www/html/writable/credentials/firebase_key.json
    chmod 600 /var/www/html/writable/credentials/firebase_key.json
    echo "Firebase credentials written successfully."
else
    echo "WARNING: FIREBASE_CREDENTIALS_BASE64 is not set. Firestore will not connect."
fi

exec "$@"
