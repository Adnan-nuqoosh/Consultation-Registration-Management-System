# Website Inquiry Submissions API

Laravel backend for collecting and securely viewing submissions from five public website forms. The admin dashboard UI is intentionally handled separately by the frontend developer.

## Stack

- Laravel 13
- PHP 8.3+
- MySQL 8+
- Laravel Sanctum bearer-token authentication
- PHPUnit 12

## Setup

1. Install PHP 8.3+, Composer 2, MySQL 8+, and the PHP extensions required by Laravel (including PDO MySQL, mbstring, OpenSSL, cURL, DOM, Fileinfo, and XML).
2. Clone the repository and install dependencies:

   ```bash
   git clone https://github.com/Adnan-nuqoosh/Consultation-Registration-Management-System.git
   cd Consultation-Registration-Management-System
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

3. Create a MySQL database and set `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`.
4. Set `APP_URL`, `CORS_ALLOWED_ORIGINS`, mail settings, `CONSULTATION_ADMIN_EMAIL`, and `WEBSITE_FORMS_ADMIN_EMAIL` as required.
5. Run migrations:

   ```bash
   php artisan migrate
   ```

6. Create the first active administrator with a strong unique password. Do not commit credentials or add a default-password seeder:

   ```bash
   php artisan tinker
   ```

   Then run:

   ```php
   App\\Models\\User::create([
       'name' => 'Website Administrator',
       'email' => 'admin@your-domain.example',
       'password' => 'REPLACE-WITH-A-STRONG-UNIQUE-PASSWORD',
       'role' => 'admin',
       'is_active' => true,
   ]);
   ```

   The User model hashes passwords automatically.

7. Start the API:

   ```bash
   php artisan serve
   ```

## Public form endpoints

- `POST /api/consultations`
- `POST /api/website-forms/contact`
- `POST /api/website-forms/sourcing`
- `POST /api/website-forms/meeting`
- `POST /api/website-forms/event`

The four generic website-form endpoints preserve the complete submitted JSON payload, including custom and nested fields. See `docs/API.md` for request examples.

## Private dashboard API

- `POST /api/auth/login` — active administrator login.
- `GET /api/auth/me` — current administrator profile.
- `POST /api/auth/logout` — revoke the current bearer token.
- `GET /api/admin/submissions` — one paginated, date-ordered list across all five forms.
- `GET /api/admin/submissions/{formType}/{id}` — complete visitor-submitted details for one record.

All dashboard endpoints require an active administrator's Sanctum bearer token. There is one administrator access level; manager/agent permissions and CRM workflows are not part of the simplified dashboard scope.

## Notifications

Submission notification emails are attempted after a public form is saved. Configure a working SMTP mailer in `.env` to deliver them. The default local mail configuration may log messages rather than deliver them. Email delivery errors do not undo a saved submission.

## Tests

```bash
vendor/bin/phpunit
```

GitHub Actions runs the feature test suite on pushes and pull requests.

## Production checklist

- Set `APP_ENV=production`, `APP_DEBUG=false`, a unique `APP_KEY`, and HTTPS `APP_URL`.
- Configure MySQL backups, SMTP, logs, and the exact production frontend origin in CORS settings.
- Serve the API over HTTPS only and restrict database/network access.
- Create named administrator accounts with unique strong passwords and deactivate unused accounts.
- Run migrations and the complete test suite against the production-equivalent database before launch.
- Confirm the frontend sends every rendered website form field in the JSON payload. File uploads require a separate implementation and are not supported by the generic payload endpoint.
