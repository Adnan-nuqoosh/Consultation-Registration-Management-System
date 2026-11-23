# Consultation Registration Management System — Laravel API

Backend API for the website consultation form and the existing Next.js Admin Dashboard. The frontend UI is intentionally outside this repository's implementation scope.

## Stack

- Laravel 13
- PHP 8.3+
- MySQL 8+
- Laravel Sanctum personal access tokens
- PHPUnit 12

Laravel 13 requires PHP 8.3 or newer. Check the official [Laravel release/support policy](https://laravel.com/docs/13.x/releases) and [deployment requirements](https://laravel.com/docs/13.x/deployment) before selecting the production runtime.

## Install locally

1. Install PHP 8.3+, Composer 2, MySQL 8+, and the PHP extensions required by Laravel (including PDO MySQL, mbstring, OpenSSL, cURL, DOM, Fileinfo, and XML).
2. Clone the repository and install dependencies:

   ```bash
   git clone https://github.com/Adnan-nuqoosh/Consultation-Registration-Management-System.git
   cd Consultation-Registration-Management-System
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

3. Create a MySQL database, then set DB_DATABASE, DB_USERNAME and DB_PASSWORD in `.env`.
4. Set `APP_URL`, `CORS_ALLOWED_ORIGINS`, mail settings, and `CONSULTATION_ADMIN_EMAIL`.
5. Run database migrations:

   ```bash
   php artisan migrate --seed
   ```

6. Create the first administrator interactively. Do not commit the password or run a default-password seeder:

   ```bash
   php artisan tinker
   ```

   Then run, replacing the example email and password with strong unique values:

   ```php
   App\\Models\\User::create([
       'name' => 'System Administrator',
       'email' => 'admin@your-domain.example',
       'password' => 'REPLACE-WITH-A-STRONG-UNIQUE-PASSWORD',
       'role' => 'admin',
       'is_active' => true,
   ]);
   ```

   The User model hashes passwords automatically. Create manager/agent accounts the same way with the appropriate `role`.

7. Start the API:

   ```bash
   php artisan serve
   ```

## API overview

Public:
- `POST /api/consultations` — submit a request (5 requests/minute/IP).
- `POST /api/auth/login` — exchange an administrator account for a Sanctum bearer token.

Authenticated:
- `GET /api/auth/me`
- `POST /api/auth/logout`
- `GET /api/admin/consultations`
- `GET /api/admin/consultations/{id}`
- `PATCH /api/admin/consultations/{id}`
- `PATCH /api/admin/consultations/{id}/status`
- `POST /api/admin/consultations/{id}/notes`
- `POST /api/admin/consultations/{id}/assign`
- `DELETE /api/admin/consultations/{id}` (soft delete; admin only)
- `GET /api/admin/consultation-stats`
- `GET /api/admin/consultations-export` (CSV)

See [API contract](docs/API.md) and [Next.js handover notes](docs/FRONTEND_HANDOFF.md).

## Roles

- `admin`: full access, including soft delete.
- `manager`: view, update, change status, add notes, assign, export and view statistics.
- `agent`: view assigned requests, update their status, add internal notes and view their own statistics.

Agent detail, status and note operations are restricted to requests assigned to that agent. Inactive users are blocked from consultation permissions.

## Notifications

After a registration is saved, the API attempts to send:
- Applicant confirmation email with the consultation reference.
- Admin notification when `CONSULTATION_ADMIN_EMAIL` is configured.

Set a working SMTP mailer in `.env` for delivery. The default `MAIL_MAILER=log` writes messages to the application log for local testing. Mail delivery failures are logged and do not undo the saved request.

## Tests

```bash
php artisan test
```

GitHub Actions runs the PHPUnit feature suite on pushes to `main` and `backend/**` and on pull requests targeting `main`.

## Production checklist

- Set `APP_ENV=production`, `APP_DEBUG=false`, a unique APP_KEY and HTTPS APP_URL.
- Configure MySQL backups, SMTP, logs, and a specific CORS origin for the production Next.js domain.
- Use HTTPS only and restrict database/network access.
- Create named admin accounts with unique strong passwords; deactivate unused accounts.
- Review retention/deletion requirements for applicant personal information.
- Run migrations and the full test suite against the production-equivalent database before launch.
