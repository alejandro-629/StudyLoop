#!/usr/bin/env bash
# One-command local setup: installs PHP dependencies, starts the Sail-compatible
# Compose stack, prepares the database and builds front-end assets.
# Usage: scripts/setup.sh            (safe to re-run)
set -euo pipefail

cd "$(dirname "$0")/.."

say() { printf '\n\033[1m==> %s\033[0m\n' "$1"; }

command -v docker >/dev/null || { echo "Docker is required: https://docs.docker.com/get-docker/" >&2; exit 1; }
docker compose version >/dev/null 2>&1 || { echo "Docker Compose v2 is required (docker compose ...)." >&2; exit 1; }

if [[ ! -f .env ]]; then
    say "Creating .env from .env.example"
    cp .env.example .env
    # Map the container user to the current host user so files stay editable.
    sed -i.bak -e "s/^WWWUSER=.*/WWWUSER=$(id -u)/" -e "s/^WWWGROUP=.*/WWWGROUP=$(id -g)/" .env
    rm -f .env.bak
fi

if [[ ! -f vendor/autoload.php ]]; then
    say "Installing Composer dependencies (throwaway PHP 8.4 container)"
    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v "$(pwd):/var/www/html" \
        -w /var/www/html \
        laravelsail/php84-composer:latest \
        composer install --no-interaction --prefer-dist --ignore-platform-reqs
fi

sail=./vendor/bin/sail

say "Building and starting containers (app, horizon, mysql, redis, mailpit)"
$sail up -d --build --wait

if ! grep -qE '^APP_KEY=.+' .env; then
    say "Generating the application key"
    $sail artisan key:generate --ansi
fi

say "Running migrations and seeding synthetic demo data"
$sail artisan migrate --seed --force

say "Installing and building front-end assets"
$sail npm install --no-audit --no-fund
$sail npm run build

app_port="$(sed -n 's/^APP_PORT=//p' .env | tail -n 1)"
app_port="${app_port:-80}"

say "Checking http://localhost:${app_port}/health"
for attempt in $(seq 1 15); do
    if curl --fail --silent --show-error "http://localhost:${app_port}/health"; then
        echo
        break
    fi
    if [[ $attempt -eq 15 ]]; then
        echo "The app did not answer /health; see '$sail logs laravel.test'." >&2
        exit 1
    fi
    sleep 2
done

say "StudyLoop is running on http://localhost:${app_port} (Mailpit: http://localhost:8025)"
