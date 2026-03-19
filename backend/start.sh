#!/bin/sh

echo "Updating database schema..."
# Retry up to 5 times (PostgreSQL takes a couple of seconds to accept connections on first boot)
for i in 1 2 3 4 5; do
  php bin/console doctrine:schema:update --force && break || sleep 2
done

# Start the built-in PHP server in the background
php -S 0.0.0.0:${PORT:-8080} -t public &

# Start the Symfony Messenger worker in the foreground
# This keeps the container alive
echo "Starting Messenger Worker..."
php bin/console messenger:consume async -vv --memory-limit=128M
