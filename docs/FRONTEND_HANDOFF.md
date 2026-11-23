# Next.js Frontend Handover

The frontend developer owns the website form and the existing Admin Dashboard UI. This backend repository supplies the JSON APIs only.

## Environment

Set a frontend server-side/public API base URL, for example:

```env
NEXT_PUBLIC_API_BASE_URL=https://api.example.com/api
```

Do not place admin bearer tokens in public client-side configuration. Keep admin tokens in the existing secure authentication/session flow used by the dashboard.

## Website form integration

Send `POST {API_BASE_URL}/consultations` with the required fields in the API contract. Use JSON and accept HTTP 201 as success.

The form may send `product_group` as a string or array. Prefer an array of selected category strings. The backend normalizes a comma-separated string into an array.

On success, show:
"Thank you for registering! Your consultation request has been successfully submitted. Our team will contact you shortly."

Use the returned reference for the confirmation screen/email. Handle HTTP 422 field errors and HTTP 429 rate-limit responses. Prevent repeated client submissions while a request is pending.

## Admin authentication

POST `/auth/login` with email/password. Store the returned token using the existing secure admin auth approach. For every admin request send:

```http
Authorization: Bearer <token>
Accept: application/json
```

Call POST `/auth/logout` when the admin signs out.

## Admin screen mapping

- Dashboard cards: GET `/admin/consultation-stats`
- Table/search/filters/pagination: GET `/admin/consultations`
- Details: GET `/admin/consultations/{id}`
- Edit applicant: PATCH `/admin/consultations/{id}`
- Status dropdown/timeline: PATCH `/admin/consultations/{id}/status`, then refresh details
- Internal notes: POST `/admin/consultations/{id}/notes`
- Assign team member: POST `/admin/consultations/{id}/assign`
- Delete action: DELETE `/admin/consultations/{id}`
- Export: GET `/admin/consultations-export` with the active filters

The assignment API currently expects a numeric user ID. An admin team/user-list endpoint is not included in this first API scope; use the existing dashboard's team-member source or request a backend endpoint addition before building a new user picker.

## CORS

Set `CORS_ALLOWED_ORIGINS` on the API server to the exact website/admin origin(s), comma-separated. Do not use wildcard origins in production. Bearer-token requests do not require cookie credentials.

## Important implementation detail

`preferred_consultation_time` is persisted as a string so the form can send a preference such as "Morning" or a date/time. The frontend and business team should confirm the allowed values and timezone convention before treating it as a scheduled appointment.
