# Backend Implementation Plan

The repository was empty at kickoff. There is no existing Laravel application, authentication setup, role model or database schema to audit.

## Phase 1 — Laravel foundation
- [ ] Scaffold Laravel application after confirming target PHP and Laravel versions.
- [ ] Add safe .env.example and setup guide.
- [ ] Configure API routes, health endpoint and test tooling.

## Phase 2 — Public registration
- [ ] ConsultationRequest model and migration.
- [ ] Unique reference generation with database-safe uniqueness.
- [ ] Form Request validation and privacy consent enforcement.
- [ ] POST endpoint with transaction and 201 response.
- [ ] Throttling and abuse protections.
- [ ] Feature tests for valid/invalid submissions and unique references.

## Phase 3 — Admin
- [ ] Select admin auth guard after confirming frontend/admin login contract.
- [ ] Implement permissions against actual user/role schema.
- [ ] Listing, filters, search, details and applicant updates.
- [ ] Status history, private notes and assignment.
- [ ] Soft-delete, stats and secure CSV export.

## Phase 4 — Notifications and handover
- [ ] Queue admin and applicant emails.
- [ ] OpenAPI docs and frontend integration examples.
- [ ] Run migrations and full tests against MySQL.
- [ ] CI workflow.

## Decisions needed
- Hosting PHP version and Laravel target.
- Admin authentication guard and role/permission schema.
- Exact allowed product group and export status values.
- Preferred consultation time: named time slot or date/time with timezone.
