#!/bin/sh

echo "Running database migrations..."
for i in 1 2 3 4 5; do
    php bin/console doctrine:migrations:migrate --no-interaction && break || sleep 2
done

exec php -S 0.0.0.0:${PORT:-8080} -t public
