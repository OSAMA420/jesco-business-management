#!/usr/bin/env bash
# Builds a clean copy of the app in .wasmer-build/ for `wasmer deploy`.
# Only runtime files go in: no node_modules, no .env, no dev dependencies.
# APP_KEY is set separately as a Wasmer secret, never shipped in the package.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
OUT="$ROOT/.wasmer-build"
export PATH="$PATH:/c/xampp/php"

cd "$ROOT"
npm run build

rm -rf "$OUT"
mkdir -p "$OUT"

cp -r app bootstrap config database public routes artisan composer.json composer.lock "$OUT/"
mkdir -p "$OUT/resources"
cp -r resources/views "$OUT/resources/"

# Local caches hold Windows paths; Wasmer serves from /app.
rm -f "$OUT"/bootstrap/cache/*.php
rm -f "$OUT/public/hot"

mkdir -p "$OUT"/storage/framework/{cache/data,sessions,views} "$OUT/storage/logs" "$OUT/storage/app/public"

cp deploy/wasmer/wasmer.toml deploy/wasmer/app.yaml "$OUT/"
# Keep the app_id Wasmer writes back after the first deploy.
if [ -f deploy/wasmer/.app_id ]; then
    echo "app_id: $(cat deploy/wasmer/.app_id)" >> "$OUT/app.yaml"
fi

cd "$OUT"
composer install --no-dev --optimize-autoloader --no-interaction

echo "Build ready in $OUT"
