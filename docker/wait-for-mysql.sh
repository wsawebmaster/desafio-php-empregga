#!/usr/bin/env bash
set -euo pipefail
host="${DB_HOST:-db}"
user="${DB_USER:-root}"
pass="${DB_PASS:-}"
echo "Waiting for MySQL at $host..."
until mysqladmin --protocol=tcp -h "$host" -u "$user" -p"$pass" ping --silent >/dev/null 2>&1; do
  sleep 1
done
echo "MySQL is up. Starting app..."
exec "$@"
