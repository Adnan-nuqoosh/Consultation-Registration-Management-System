# Backend Implementation Plan

The repository was empty when work began. No pre-existing Laravel app, auth setup, role model or database schema was available to audit. A fresh Laravel API foundation has therefore been added on a dedicated branch.

## Implemented in this branch
- [x] Laravel 13 project foundation with PHP 8.3+ requirement.
- [x] Public consultation registration endpoint with validation, privacy consent, IP throttling and unique reference number.
- [x] MySQL migrations for users, requests, notes, status history and Sanctum tokens.
- [x] Admin bearer authentication and admin/manager/agent access rules.
- [x] Listing, search, status/date/assignee filters and pagination.
- [x] Details, applicant editing, status update/history, internal notes and assignment.
- [x] Dashboard statistics and permission-protected CSV export.
- [x] Applicant confirmation and optional admin notification email attempts.
- [x] PHPUnit feature tests and GitHub Actions workflow definition.
- [x] API contract and Next.js handover notes.

- [x] Add flexible Contact, Sourcing, Meeting and Event form APIs with separate admin management, status history, internal notes, assignment, CSV export and notifications.
- [x] Add automated tests for all four form types and admin workflows.

## Still to verify before production
- [x] Composer dependency installation and GitHub Actions CI run: passed (10 PHPUnit tests, 34 assertions; SQLite in-memory).
- [ ] Run migrations against MySQL 8 (CI currently uses SQLite in-memory for feature tests).
- [ ] Configure real SMTP and verify both email destinations.
- [ ] Confirm exact allowed product groups, export-status options and preferred-time format with the business/frontend team.
- [ ] Review production CORS domains, HTTPS, logging, database backups and data retention.
- [ ] Have the frontend developer map all exact staging fields, select values and any file inputs to the generic payload contract; staging URL was unavailable during API work.
- [ ] Have the frontend developer integrate and test these forms and the existing Consultation form inside the existing dashboard.

## Decisions / assumptions
- The project targets Laravel 13 and PHP 8.3+.
- Bearer-token authentication uses Laravel Sanctum because no existing admin auth system was present in the empty repository.
- Roles are admin, manager and agent, with permissions defined in the application provider. Agents are restricted to requests assigned to them.
- Product groups are stored as a JSON array.
- Preferred consultation time is a string preference, not automatically interpreted as a calendar booking.
- Delete is implemented as soft delete.
