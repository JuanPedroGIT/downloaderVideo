#!/bin/sh

# Start the built-in PHP server in the background
php -S 0.0.0.0:8080 -t public &

# Start the Symfony Messenger worker in the foreground
# This keeps the container alive
echo "Starting Messenger Worker..."
php bin/console messenger:consume async -vv --memory-limit=128M
