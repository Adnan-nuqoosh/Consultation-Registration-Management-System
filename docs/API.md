# Consultation API Contract

Base path: /api. JSON requests and responses.

## Public registration
POST /api/consultations

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

All fields are required. Validate email and international phone syntax, require privacy_consent=true, and preserve selected product groups as an array/JSON unless the frontend contract agrees otherwise. Preferred time may be a period such as Morning or an ISO-8601 datetime; the frontend and backend must agree which format is used.

Success (201):
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

Errors: 422 validation, 429 throttling, 500 generic error (details only in server logs).

## Admin routes
All require an authenticated administrator and explicit permissions.
- GET /api/admin/consultations — pagination and search/status/date/assignee filters.
- GET /api/admin/consultations/{id} — complete details, private notes and status history.
- PATCH /api/admin/consultations/{id} — applicant information.
- PATCH /api/admin/consultations/{id}/status — status and optional note; write history atomically.
- POST /api/admin/consultations/{id}/notes — private internal note.
- POST /api/admin/consultations/{id}/assign — assign eligible team member.
- DELETE /api/admin/consultations/{id} — privileged action; prefer soft delete.
- GET /api/admin/consultations-export — filtered CSV, permission protected.
- GET /api/admin/consultation-stats — total and per-status counts.

Statuses: New, Under Review, Contacted, Consultation Scheduled, In Progress, Completed, Cancelled.

CSV export must neutralize formula-injection cells. Public responses must never expose internal notes, assignment information or status history.
