# Telegram Health Tracker

A personal Laravel application that records blood glucose and blood pressure readings through a Telegram bot. It uses Telegram long polling, so no public server, domain, or webhook is required. This tool stores readings only; it does not provide medical diagnoses or medication advice.

## Requirements

PHP 8.3+, Composer, MySQL 8+, and a Telegram bot token. The app is configured for `Asia/Kuala_Lumpur`.

## Install and configure

```bash
composer install
cp .env.example .env
php artisan key:generate
mysql -u root -p -e 'CREATE DATABASE telegram_health_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
```

Edit `.env` with your MySQL credentials and Telegram settings:

```env
APP_TIMEZONE=Asia/Kuala_Lumpur
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=telegram_health_tracker
DB_USERNAME=root
DB_PASSWORD=

TELEGRAM_BOT_TOKEN=123456:replace-with-your-token
TELEGRAM_ALLOWED_USER_IDS=123456789
TELEGRAM_POLL_TIMEOUT=30
TELEGRAM_DEFAULT_GLUCOSE_UNIT=mmol/L
```

Use comma-separated IDs for more than one allowed person. Create a bot through Telegram's BotFather (`/newbot`). To find your numeric Telegram user ID, message a bot such as `@userinfobot`, then add only your ID to `TELEGRAM_ALLOWED_USER_IDS`.

Run the database migrations and validate the token:

```bash
php artisan migrate
php artisan telegram:test
php artisan telegram:set-menu
php artisan telegram:poll
```

On macOS, leave the polling command running in a Terminal window (or use a launch agent). Stop it with `Ctrl+C`. Do not start two pollers: the application acquires a lock to prevent this.

## Docker development

Docker Desktop is the only additional requirement. The Compose stack contains Nginx, PHP-FPM/Laravel, MySQL 8.4, a one-off migration service, and the Telegram polling worker. It does not expose MySQL beyond your local machine.

```bash
cp .env.example .env
# Add TELEGRAM_BOT_TOKEN and TELEGRAM_ALLOWED_USER_IDS to .env
docker compose build
docker compose run --rm --no-deps app php artisan key:generate
docker compose up -d
```

The application is available at `http://localhost:8080`. Compose uses the `laravel` MySQL application account and the development password in `DOCKER_DB_PASSWORD`; change these values before sharing the environment. The MySQL database is retained in the `mysql-data` Docker volume.

Useful commands:

```bash
docker compose ps
docker compose logs -f telegram-worker
docker compose exec app php artisan telegram:test
docker compose exec app php artisan telegram:set-menu
docker compose exec app php artisan test
docker compose down
```

`docker compose down` stops services but preserves the database volume. To intentionally remove all local Docker data, use `docker compose down --volumes`; this deletes the MySQL development database.

## NVIDIA DGX Spark deployment

The bot does not require an inbound public IP, domain, or webhook: the `telegram-worker` uses outbound Telegram long polling. The production stack keeps MySQL inside Docker and binds the optional web endpoint only to `127.0.0.1:8080`, so it is not exposed to the network. You can reach it from your Mac through an SSH tunnel if needed:

```bash
ssh -L 8080:127.0.0.1:8080 YOUR_DGX_USER@YOUR_DGX_HOST
```

On the DGX Spark, install Docker Engine with the Compose plugin, copy or clone this repository, then run:

```bash
cd /path/to/laravel_tg_blood_sugar_pressure_record
cp .env.dgx.example .env
chmod 600 .env
# Edit .env: set APP_KEY, the two MySQL passwords, bot token, and allowed Telegram ID.
docker compose -f docker-compose.production.yml build
docker compose -f docker-compose.production.yml up -d
docker compose -f docker-compose.production.yml ps
docker compose -f docker-compose.production.yml logs -f telegram-worker
```

If the first image build fails while installing PHP extensions, make sure the current `Dockerfile` starts with `php:8.4-fpm-bookworm` (not an Alpine PHP image), then rebuild from scratch with plain progress output:

```bash
docker compose -f docker-compose.production.yml build --no-cache --progress=plain
docker compose -f docker-compose.production.yml up -d
```

The Bookworm image installs the PHP compiler prerequisites and builds the MySQL, `mbstring`, and `intl` extensions; it is the supported DGX build path.

Generate a key before the first `up` if you have not set one manually:

```bash
docker compose -f docker-compose.production.yml run --rm --no-deps app php artisan key:generate --show
```

Copy the printed key into `APP_KEY` in `.env`, then start the stack. Confirm the bot token and publish the Telegram menu:

```bash
docker compose -f docker-compose.production.yml exec app php artisan telegram:test
docker compose -f docker-compose.production.yml exec app php artisan telegram:set-menu
```

For updates, pull the new source, rebuild, and recreate services. The named `mysql-data` volume is retained:

```bash
docker compose -f docker-compose.production.yml up -d --build
```

Back up data from the DGX without exposing MySQL:

```bash
docker compose -f docker-compose.production.yml exec -T db sh -c 'exec mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' > telegram-health-backup.sql
```

Do not run `docker compose down --volumes` on the DGX unless you deliberately want to destroy the database volume.

## Bot commands

```text
/sugar 7.2 fasting
/sugar 126 mg/dL before meal
/sugar 7.2 fasting felt dizzy
/bp 128 82
/bp 128 82 72 after walking
/latest
/today
/week
/delete_last
/confirm_delete
```

`/delete_last` never removes data immediately; it requires `/confirm_delete`. Glucose averages in `/week` remain separated by unit.

## Ubuntu systemd

Create `/etc/systemd/system/telegram-health-tracker.service` (replace the username and paths):

```ini
[Unit]
Description=Laravel Telegram Health Tracker
After=network-online.target

[Service]
Type=simple
User=james
WorkingDirectory=/path/to/project
ExecStart=/usr/bin/php artisan telegram:poll
Restart=always
RestartSec=5
Environment=APP_ENV=production

[Install]
WantedBy=multi-user.target
```

Then run:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now telegram-health-tracker
sudo systemctl status telegram-health-tracker
sudo systemctl restart telegram-health-tracker
sudo systemctl stop telegram-health-tracker
```

## Backup and privacy

Back up MySQL regularly, encrypt the backup, and keep it outside the application host:

```bash
mysqldump -u root -p telegram_health_tracker > telegram_health_tracker-$(date +%F).sql
```

Keep `.env` private; it contains the bot token and database password. Only configured Telegram IDs can access the bot, private chats are required by default, group messages are ignored, and no token or readings are intentionally logged. Use a strong MySQL password and restrict database access to the host where possible.

## Development

```bash
php artisan test
vendor/bin/pint
```
