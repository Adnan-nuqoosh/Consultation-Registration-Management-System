# Website Inquiry Submissions API

Base URL example: `https://api.example.com/api`. JSON is used for requests and responses. Private dashboard routes require `Authorization: Bearer {token}`.

## Public form endpoints

### Consultation

`POST /consultations`

Example request:

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

All fields are required. `product_group` accepts an array or a comma-separated string and is stored as an array. `privacy_consent` must be accepted. Phone numbers are validated for 7–15 digits. The endpoint is limited to 5 requests per IP per minute.

### Contact, Sourcing, Meeting and Event

Use one endpoint for each existing website form:

- `POST /website-forms/contact`
- `POST /website-forms/sourcing`
- `POST /website-forms/meeting`
- `POST /website-forms/event`

Preferred JSON body:

```json
{
  "payload": {
    "full_name": "Ayesha Khan",
    "company": "ABC Trading LLC",
    "email": "ayesha@example.com",
    "phone": "+971501234567",
    "message": "Please contact me about your services.",
    "custom_nested_field": {
      "selection": ["Option A", "Option B"]
    }
  }
}
```

The backend stores the full payload, including unknown form-specific keys, arrays and nested values. Flat JSON bodies are also accepted. Common contact values are extracted for the list view when recognised. Email and phone values are validated when provided. Do not populate the reserved `_website_trap` honeypot field.

All public endpoints return HTTP 201 with a submission ID, reference and form type. Invalid requests return HTTP 422; rate-limited requests return HTTP 429.

## Authentication

### POST /auth/login

Only active users whose account role is `admin` can log in.

```json
{
  "email": "admin@your-domain.example",
  "password": "your-strong-password",
  "device_name": "Website Inquiry Dashboard"
}
```

The response contains `data.token` and `data.token_type = "Bearer"`. Login is limited to 5 attempts per minute per IP/email combination.

### GET /auth/me

Requires a bearer token. Returns the current administrator's ID, name and email.

### POST /auth/logout

Requires a bearer token. Revokes the current token; use this endpoint when the administrator logs out.

## Private dashboard endpoints

Both endpoints require a valid Sanctum bearer token belonging to an active administrator. Unauthenticated requests return HTTP 401; authenticated non-admin or inactive users receive HTTP 403.

### GET /admin/submissions

Returns one combined list containing Consultation, Contact, Sourcing, Meeting and Event submissions, sorted newest first.

Optional query parameters:

| Parameter | Purpose |
|---|---|
| `form_type` | Filter to `consultation`, `contact`, `sourcing`, `meeting`, or `event` |
| `from` | Include submissions on or after this date (`YYYY-MM-DD`) |
| `to` | Include submissions on or before this date (`YYYY-MM-DD`) |
| `per_page` | Page size from 1 to 100; default 25 |

Example: `GET /admin/submissions?form_type=contact&from=2026-11-01&per_page=25`

Example response:

```json
{
  "success": true,
  "data": [
    {
      "id": 42,
      "reference": "WEB-CONTACT-20261123-A1B2C3D4",
      "form_type": "contact",
      "name": "Ayesha Khan",
      "company": "ABC Trading LLC",
      "email": "ayesha@example.com",
      "phone": "+971501234567",
      "submitted_at": "2026-11-23T10:30:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 25,
    "total": 1
  }
}
```

The `id` is scoped to its form table. Always use both `form_type` and `id` when opening a submission.

### GET /admin/submissions/{formType}/{id}

Returns all information entered by the visitor for one selected form.

Allowed `formType` values: `consultation`, `contact`, `sourcing`, `meeting`, `event`.

Example: `GET /admin/submissions/meeting/42`

The response contains the record metadata and a `details` object. For Consultation, `details` contains every field from the consultation form. For the other forms, `details` is the complete original JSON payload, including custom and nested fields.

## Access and scope

The simplified dashboard provides only:
- Secure administrator login and logout.
- One combined list of all five website form submission types.
- Complete read-only details for each submission.

No CRM roles, assignment, notes, follow-up tasks, status workflows, analytics, dashboard UI, or export endpoints are included in this API scope.

## Deployment notes

- Configure a specific production frontend origin in CORS settings.
- Use HTTPS for both frontend and API.
- Configure SMTP separately if submission notification emails are required.
- The generic form endpoint stores JSON fields; file uploads are not supported.
