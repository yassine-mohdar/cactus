# NinoWorld Backend

Laravel 13 modular-monolith backend for NinoWorld commerce, operations, customer account, finance, shipping, CMS, and notifications.

## Current State

- Release-candidate backend
- Primary local database contract: MySQL
- Primary online gateway: CMI
- Secondary supported gateways: Payzone, Stripe
- Offline payment support: bank transfer and COD
- Notifications: email, SMS, WhatsApp
- Full automated suite status: `308` tests passing

## Stack

- PHP `8.4+`
- Laravel `13`
- MySQL `8+`
- Node `23+`
- npm `10+`
- Database-backed queue, session, and cache by default

## First Run

1. Install dependencies:

```bash
composer install
npm install
```

2. Copy env and generate key:

```bash
cp .env.example .env
php artisan key:generate
```

3. Configure MySQL in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nino-backend
DB_USERNAME=root
DB_PASSWORD=
DB_SOCKET=
```

Important:
- Leave `DB_SOCKET` empty unless you know the exact working socket path.
- The app uses database-backed `sessions`, `cache`, and `jobs`, so MySQL must be running before the app can serve requests correctly.

4. Prepare the database:

```bash
php artisan migrate
php artisan db:seed
```

5. Build frontend assets:

```bash
npm run build
```

## Local Runbook

Run the HTTP server:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Run the queue worker in another terminal:

```bash
php artisan queue:work --queue=notifications,default --tries=3
```

Run Vite in development if you need live assets:

```bash
npm run dev
```

Open:

- App: `http://127.0.0.1:8000`
- Admin login: `http://127.0.0.1:8000/admin/login`
- Customer login: `http://127.0.0.1:8000/login`

## Release-Candidate Checklist

Before calling the backend ready in a real environment:

- MySQL is running and reachable on the configured host/port
- `php artisan migrate --force` succeeds
- `php artisan test` passes
- SMTP credentials are valid if email delivery is enabled
- Twilio credentials are valid if SMS delivery is enabled
- WhatsApp API credentials are valid if WhatsApp delivery is enabled
- Queue worker is running for notification dispatch
- Gateway settings are configured for CMI in admin
- Secondary gateways are disabled unless intentionally used

## Payment Matrix

- `cmi`: primary live gateway
- `payzone`: supported secondary gateway
- `stripe`: supported secondary gateway
- `bank_transfer` / `offline_transfer`: release-ready offline path
- `cash_on_delivery`: supported order flow without gateway initiation

Checkout returns:

- offline payment instructions for bank transfer
- redirect/form payloads for online gateways
- signed thank-you URL for order follow-up

## Notifications

Release-blocking operational notifications are wired through real flows:

- `order_placed`
- `payment_success`
- `payment_failed`
- `order_shipped`
- `order_delivered`
- `order_cancelled`
- `welcome`
- `password_setup`

## Useful Commands

```bash
php artisan test
php artisan migrate:status
php artisan about
php artisan queue:work --queue=notifications,default --tries=3
php artisan config:clear
php artisan cache:clear
```

## Known Non-Blocking Scope Decision

Phase `2A` is intentionally deferred. It does not block backend release readiness.
