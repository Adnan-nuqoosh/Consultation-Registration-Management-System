# Consultation Management API Contract

Base URL example: `https://api.example.com/api`

JSON is used unless an endpoint returns a CSV download. Protected routes require `Authorization: Bearer {token}`.

## Public registration

### POST /consultations

No authentication required. Rate limit: 5 requests per IP per minute.

Request:
```json
{
  "full_name": "Ahmed Mohamed",
  "company": "ABC Trading LLC",
  "job_title": "Export Manager",
  "email": "ahmed@example.com",
  "phone": "+971501234567",
  "product_group": ["Coffee", "Dairy"],
  "export_status": "Already exporting",
  "support_needed": "Export market expansion and business consultation",
  "preferred_consultation_time": "Morning",
  "privacy_consent": true
}
```

All fields are required. `product_group` accepts an array of strings or a comma-separated string (the API stores a normalized array). `privacy_consent` must be accepted. Phone accepts international-style punctuation but must contain 7–15 digits. `preferred_consultation_time` is stored as the submitted preference, e.g. "Morning", "Afternoon", or a date/time string agreed with the frontend.

Success: HTTP 201
```json
{
  "success": true,
  "message": "Consultation request submitted successfully",
  "data": {
    "id": 125,
    "reference": "CONS-2026-00125",
    "status": "New"
  }
}
```

Validation failure: HTTP 422 with Laravel's standard `message` and `errors` object. Rate limit: HTTP 429.

## Authentication

### POST /auth/login

```json
{
  "email": "admin@your-domain.example",
  "password": "your-strong-password",
  "device_name": "Next.js Admin Dashboard"
}
```

Returns `data.token` and `data.token_type = Bearer`. Login is limited to 5 attempts per minute per IP/email combination.

### GET /auth/me
Returns the authenticated user profile.

### POST /auth/logout
Revokes the current personal access token.

## Admin endpoints

All routes require Sanctum bearer authentication and role permissions.

### GET /admin/consultations

Query parameters:
- `search`: reference, name, company or email contains.
- `status`: one of the allowed statuses.
- `from`, `to`: submission date range (YYYY-MM-DD).
- `assigned_to`: user ID (admin/manager scope).
- `per_page`: 1–100; default 25.

Response has `success`, `data` array and `meta` pagination fields.

Agents only see requests assigned to their own account.

### GET /admin/consultations/{id}

Returns applicant information, privacy consent, assignee, internal notes and complete status history. Agents receive 404 for records not assigned to them.

### PATCH /admin/consultations/{id}

Accepts any subset of applicant fields from the public request schema. Validation is applied to submitted fields.

### PATCH /admin/consultations/{id}/status

```json
{
  "status": "Contacted",
  "note": "Called the applicant; awaiting documents."
}
```

Allowed statuses: `New`, `Under Review`, `Contacted`, `Consultation Scheduled`, `In Progress`, `Completed`, `Cancelled`.

Each actual status change is stored in `consultation_status_histories` with actor, previous/current status, note and timestamp. Re-submitting the current status does not create a duplicate history row.

### POST /admin/consultations/{id}/notes

```json
{ "note": "Internal follow-up note; not visible to the applicant." }
```

Creates a private note with its author and timestamp.

### GET /admin/team-members
Returns active manager/agent user choices for the assignment picker. Requires assignment permission.

### POST /admin/consultations/{id}/assign

```json
{ "assigned_to": 18 }
```

Only active users with role `manager` or `agent` can be assigned.

### GET /admin/consultation-stats

Returns total and counts by all seven statuses. Agent totals are limited to their assigned requests.

### GET /admin/consultations-export

Downloads filtered CSV. Uses the same search/status/date/assignee filters as the list endpoint, streams results in chunks, and neutralizes spreadsheet formula-leading cells.

Applicant edits, note additions, assignment and soft deletion are also recorded in the activity log returned by the details endpoint.

### DELETE /admin/consultations/{id}

Admin role only. Uses soft deletion.

## Access matrix

| Action | Admin | Manager | Agent |
|---|---:|---:|---:|
| List/view | Yes | Yes | Assigned only |
| Edit applicant fields | Yes | Yes | No |
| Change status | Yes | Yes | Assigned only |
| Add internal note | Yes | Yes | Assigned only |
| Assign request | Yes | Yes | No |
| Export CSV | Yes | Yes | No |
| View statistics | Yes | Yes | Assigned only |
| Delete (soft) | Yes | No | No |

## Data privacy

The public creation response contains only the record ID, reference and initial status. Internal notes, status history and assignment are only available through authorized admin endpoints.

---

# Additional Website Forms API (Contact / Sourcing / Meeting / Event)

The generic endpoint stores the full form payload without discarding form-specific fields. Exact staging form field names should be mapped by the frontend developer after checking the live rendered forms.

## Public submission

`POST /api/website-forms/{formType}`

Allowed `formType` values: `contact`, `sourcing`, `meeting`, `event`.

The endpoint accepts either a `payload` object or flat form fields. A honeypot field named `_website_trap` must remain empty. Requests are rate-limited to 5 per IP per minute by default.

Example:
```json
{
  "payload": {
    "full_name": "Ayesha Khan",
    "company": "ABC Trading LLC",
    "email": "ayesha@example.com",
    "phone": "+971501234567",
    "subject": "General enquiry",
    "message": "Please contact me about your services.",
    "event_name": "Export Forum",
    "custom_field_from_the_form": "Preserved exactly in payload"
  }
}
```

The backend stores the entire payload JSON. It additionally extracts common contact fields into indexed columns when these aliases are supplied: name/full_name/contact_name/your_name; company/company_name/organization/business_name; email/email_address/contact_email; phone/phone_number/mobile/mobile_number/telephone. Email and phone are format-checked when supplied.

Success (201):
```json
{
  "success": true,
  "message": "Contact form submitted successfully.",
  "data": {
    "id": 42,
    "reference": "WEB-CONTACT-20261123-A1B2C3D4",
    "form_type": "contact",
    "status": "New"
  }
}
```

## Admin management

All admin routes require a Sanctum bearer token and the relevant `website_forms.*` permission.

- `GET /api/admin/website-forms`: paginated list; filters: `form_type`, `status`, `search`, `from`, `to`, `assigned_to`, `per_page`.
- `GET /api/admin/website-forms/{id}`: full payload, assignee, internal notes, status history and audit log.
- `PATCH /api/admin/website-forms/{id}`: replace/update the stored `payload` object.
- `PATCH /api/admin/website-forms/{id}/status`: body `{"status":"Contacted","note":"Called the contact" }`.
- `POST /api/admin/website-forms/{id}/notes`: body `{"note":"Internal note"}`.
- `POST /api/admin/website-forms/{id}/assign`: body `{"assigned_to":12}`.
- `DELETE /api/admin/website-forms/{id}`: admin-only soft delete.
- `GET /api/admin/website-forms-stats`: total, counts by form type and status.
- `GET /api/admin/website-forms-export`: filtered CSV export (includes payload JSON).

Statuses: New, Under Review, Contacted, Consultation Scheduled, In Progress, Completed, Cancelled.

Role access mirrors Consultation requests: admin full access, manager cannot delete, agent can only view/change status/add notes for records assigned to that agent. Applicant notifications are sent when an email field is detected; optional admin notifications use `WEBSITE_FORMS_ADMIN_EMAIL` (falls back to `CONSULTATION_ADMIN_EMAIL`).

