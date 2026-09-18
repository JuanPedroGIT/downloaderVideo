#!/bin/bash
set -e

HOST="${POSTGRES_HOST:-shared-postgres-db}"
SUPERUSER="${POSTGRES_USER:-postgres}"
PASS="${MEDIATOOLS_DB_PASS:-mediatools_pass}"

until PGPASSWORD="$POSTGRES_PASSWORD" pg_isready -h "$HOST" -U "$SUPERUSER" -q; do
  echo "Waiting for postgres..."
  sleep 2
done

PGPASSWORD="$POSTGRES_PASSWORD" psql -h "$HOST" -U "$SUPERUSER" -d postgres <<-EOSQL
	DO \$\$ BEGIN
	  CREATE USER mediatools WITH ENCRYPTED PASSWORD '$PASS';
	EXCEPTION WHEN duplicate_object THEN NULL;
	END \$\$;

	SELECT 'CREATE DATABASE mediatools OWNER mediatools'
	WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'mediatools')\gexec

	GRANT ALL PRIVILEGES ON DATABASE mediatools TO mediatools;
EOSQL

PGPASSWORD="$POSTGRES_PASSWORD" psql -h "$HOST" -U "$SUPERUSER" -d mediatools \
  -c "GRANT ALL ON SCHEMA public TO mediatools;"

echo "mediatools database ready."
