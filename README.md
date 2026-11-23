# Consultation Registration Management System — Backend

Laravel API backend for the Consultation Registration Management System. The Next.js website form and admin dashboard UI are owned by the frontend developer.

## Planned API
Public: POST /api/consultations
Admin: GET /api/admin/consultations, GET /api/admin/consultations/{id}, PATCH /api/admin/consultations/{id}, PATCH /api/admin/consultations/{id}/status, POST /api/admin/consultations/{id}/notes, POST /api/admin/consultations/{id}/assign, DELETE /api/admin/consultations/{id}, GET /api/admin/consultations-export, GET /api/admin/consultation-stats.

## Implementation status
This commit establishes documentation only. It is not yet a runnable Laravel application and has not been runtime-tested. Laravel scaffold, migrations, controllers, authentication, authorization, automated tests and CI are still required.

## Requirements
- PHP 8.3+ (target Laravel 12; confirm hosting compatibility)
- Composer
- MySQL 8+

## Security
- Rate-limit public registration and mitigate automated abuse.
- Admin routes require authentication and explicit permissions.
- Internal notes and assignment details must never be public.
- Never commit credentials, APP_KEY, production environment files or secrets.
