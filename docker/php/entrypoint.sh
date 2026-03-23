#!/bin/sh
set -e

# Ensure composer dependencies are installed.
# In development the source is volume-mounted, but vendor/ may not exist
# on the host. Running as root ensures this works regardless of the host
# user's UID/GID.
if [ ! -f /var/www/vendor/autoload.php ]; then
    echo "[entrypoint] vendor/ not found — running composer install..."
    cd /var/www && composer install --no-interaction --optimize-autoloader
fi

exec "$@"
