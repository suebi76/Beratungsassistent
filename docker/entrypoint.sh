#!/bin/sh
set -eu

DATA_ROOT="${BERATUNGSASSISTENT_DATA_DIR:-/data}"

mkdir -p "$DATA_ROOT/config" "$DATA_ROOT/rag/chunks" "$DATA_ROOT/rag/uploads"
touch "$DATA_ROOT/rag/chunks/.gitkeep" "$DATA_ROOT/rag/uploads/.gitkeep"
chown -R www-data:www-data "$DATA_ROOT" || true

exec docker-php-entrypoint "$@"
