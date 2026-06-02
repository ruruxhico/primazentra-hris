#!/bin/bash
set -e

# Fix Apache MPM conflict at runtime: directly remove conflicting MPM symlinks.
# a2dismod with multiple args fails atomically if any module is missing,
# silently leaving mpm_event enabled alongside mpm_prefork.
rm -f /etc/apache2/mods-enabled/mpm_event.load \
      /etc/apache2/mods-enabled/mpm_event.conf \
      /etc/apache2/mods-enabled/mpm_worker.load \
      /etc/apache2/mods-enabled/mpm_worker.conf
a2enmod mpm_prefork 2>/dev/null || true

if [ -n "${FIREBASE_CREDENTIALS_BASE64:-}" ]; then
    install -d -m 700 -o www-data -g www-data /var/www/html/writable/credentials
    install -m 600 -o www-data -g www-data /dev/null /var/www/html/writable/credentials/firebase_key.json
    printf %s "$FIREBASE_CREDENTIALS_BASE64" | base64 -d > /var/www/html/writable/credentials/firebase_key.json
    echo "Firebase credentials written successfully."
else
    echo "WARNING: FIREBASE_CREDENTIALS_BASE64 is not set. Firestore will not connect."
fi

exec "$@"
