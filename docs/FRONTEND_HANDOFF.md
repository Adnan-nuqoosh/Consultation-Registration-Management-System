# Next.js Frontend Handover — Existing Website Forms

The frontend developer owns the website forms and the existing Admin Dashboard UI. This backend adds APIs only and does not build or replace any UI.

## Public form endpoints

Use the endpoint corresponding to the existing website form:
- Contact: `POST {API_BASE_URL}/website-forms/contact`
- Sourcing: `POST {API_BASE_URL}/website-forms/sourcing`
- Meeting: `POST {API_BASE_URL}/website-forms/meeting`
- Event: `POST {API_BASE_URL}/website-forms/event`

Send JSON. Preferred format:
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

The endpoint also accepts flat JSON fields for compatibility. Send every existing form field within the payload; the backend stores unknown/custom field names and multi-select values without requiring a backend schema change. Do not send a honeypot value: the reserved `_website_trap` field must be empty or omitted.

The backend extracts common contact values from aliases:
- Name: `full_name`, `name`, `contact_name`, `your_name`
- Company: `company`, `company_name`, `organization`, `business_name`
- Email: `email`, `email_address`, `contact_email`
- Phone: `phone`, `phone_number`, `mobile`, `mobile_number`, `telephone`

All original fields are retained under `data.payload` in the admin detail response. A valid email/phone is enforced when those common keys are provided. Limit the payload to 80 top-level fields and 50 KB. The endpoint is rate-limited at 5 requests per IP per minute by default.

On success (201), show your existing form success state and store/display the returned reference. Handle 422 validation and 429 rate-limit errors.

## Existing Consultation form

Keep using the existing endpoint `POST /consultations`; do not route consultation form submissions through the generic form endpoint. See `docs/API.md` for its request contract.

## Admin dashboard integrations

The frontend should integrate these sections inside the existing dashboard:
- Website form summary cards: GET `/admin/website-forms-stats`
- List/table, filtering and pagination: GET `/admin/website-forms`
- View details and all dynamic fields: GET `/admin/website-forms/{id}`
- Edit form data: PATCH `/admin/website-forms/{id}`
- Update status/history: PATCH `/admin/website-forms/{id}/status`
- Internal notes: POST `/admin/website-forms/{id}/notes`
- Assignment choices: GET `/admin/team-members`
- Assign: POST `/admin/website-forms/{id}/assign`
- Delete: DELETE `/admin/website-forms/{id}`
- CSV export: GET `/admin/website-forms-export` with the active filters

All admin routes require `Authorization: Bearer <token>`.

## Important staging mapping note

The staging URL was not accessible from the development network when these APIs were added. These APIs therefore accept and preserve flexible form payloads rather than assuming unverified field names. Before frontend integration is signed off, inspect each live form and verify that every rendered field name/value is sent in `payload`, and confirm that any select/radio option values and file-upload controls have the required backend treatment. The current generic payload API handles text, number, boolean, arrays and nested objects; it does not accept file uploads.
