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

### POST /admin/consultations/{id}/assign

```json
{ "assigned_to": 18 }
```

Only active users with role `manager` or `agent` can be assigned.

### GET /admin/consultation-stats

Returns total and counts by all seven statuses. Agent totals are limited to their assigned requests.

### GET /admin/consultations-export

Downloads filtered CSV. Uses the same search/status/date/assignee filters as the list endpoint, streams results in chunks, and neutralizes spreadsheet formula-leading cells.

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
