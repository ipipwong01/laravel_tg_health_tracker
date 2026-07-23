#!/usr/bin/env sh
set -eu

docker compose -f docker-compose.production.yml ps
docker compose -f docker-compose.production.yml logs --tail=100 telegram-worker
