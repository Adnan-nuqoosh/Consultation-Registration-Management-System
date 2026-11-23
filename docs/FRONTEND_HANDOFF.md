# Frontend Handover — Simple Website Inquiry Dashboard API

The backend in this repository provides APIs only. The frontend developer is responsible for building the simple private dashboard UI and connecting the existing website forms. Do not add CRM modules or task workflows.

## Login and logout

1. Send `POST {API_BASE_URL}/auth/login` with `email`, `password`, and optional `device_name`.
2. Store the returned bearer token using the frontend application's approved secure token strategy.
3. Send `Authorization: Bearer <token>` with all private dashboard requests.
4. Use `GET {API_BASE_URL}/auth/me` to restore the signed-in administrator profile.
5. Send `POST {API_BASE_URL}/auth/logout` on logout and clear the local token.

Only active administrator accounts can sign in.

## Public website form submissions

Keep each existing website form and its branding/design. Connect its submit handler to:

- Consultation: `POST {API_BASE_URL}/consultations`
- Contact: `POST {API_BASE_URL}/website-forms/contact`
- Sourcing: `POST {API_BASE_URL}/website-forms/sourcing`
- Meeting: `POST {API_BASE_URL}/website-forms/meeting`
- Event: `POST {API_BASE_URL}/website-forms/event`

Consultation uses its documented fixed field contract. The other four endpoints accept a `payload` object and preserve all custom, nested and array fields. Example:

```json
{
  "payload": {
    "full_name": "Ayesha Khan",
    "email": "ayesha@example.com",
    "company": "ABC Trading LLC",
    "phone": "+971501234567",
    "message": "Form-specific fields go here."
  }
}
```

The four generic endpoints also accept flat JSON fields. The reserved `_website_trap` honeypot field must be empty or omitted. Current generic payload handling supports text, numbers, booleans, arrays and nested objects; it does not support file uploads.

## Dashboard list

Use `GET {API_BASE_URL}/admin/submissions`.

Optional query parameters:
- `form_type`: consultation, contact, sourcing, meeting, or event.
- `from` and `to`: submission date range in `YYYY-MM-DD` format.
- `per_page`: 1–100; default 25.

The response is a single newest-first list across all five form types. Each row includes ID, reference, form type, visitor name, company, email, phone and submission date/time. Use pagination fields under `meta`.

## Submission detail

Use `GET {API_BASE_URL}/admin/submissions/{formType}/{id}`.

The form type is required because IDs are generated separately in the underlying form tables. Render every key from `data.details`; for Contact, Sourcing, Meeting and Event this is the full original payload. For Consultation it contains all consultation form fields.

## UI scope

Build only:
- Administrator login/logout.
- One submissions list, including submission date/time and form type.
- A simple form-type/date filter, if desired.
- A details view showing every field the visitor submitted.

Do not add task assignment, CRM workflows, internal notes, complex roles, analytics, or advanced reports.

## Integration verification

The staging website could not be inspected during backend implementation. Before sign-off, verify that each actual website form sends every rendered field using its correct value and key. Confirm select/radio option values with the website owner. File-upload fields need a separately agreed backend change.
