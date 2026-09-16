#!/usr/bin/env bash

set -euo pipefail

cd "$(dirname "$0")/.."

docker compose up -d php
docker compose exec php composer install
