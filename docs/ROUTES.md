# Web routes and integration contract

This MVP serves HTML forms and pages. It does **not** expose a public token-based REST API. Its HTTP routes use the web session cookie and Laravel CSRF protection. This avoids an undocumented integration surface. A future API should use an explicit versioned contract, token scopes, object authorization, pagination and request rate limits (for example Laravel Sanctum), with its own tests.

## Authentication

`GET /login` renders sign-in. `POST /login` accepts `email`, `password`, `_token`. On success it rotates the session and redirects to `/`; failures redirect with validation messages. `POST /logout` requires authentication and CSRF, invalidates the session and redirects to login. `PUT /profile/password` accepts `current_password`, `password`, `password_confirmation` and `_token`.

Browser forms use hidden `_method=PUT` or `_method=DELETE` with POST for method spoofing. Cookies use HttpOnly and SameSite Lax; set Secure in HTTPS production. Never treat a successful login cookie as permission to bypass `Access::check`.

## Resource routes

Allowed `module` values: `projects`, `contractors`, `contracts`, `tasks`, `daily_logs`, `safety_incidents`, `change_orders`, `materials`, `equipment`, `documents`, `expenses`, `invoices`, `payments`, `users`. Unrecognized modules return 404 before any table access.

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/` | Dashboard scoped by module permissions. |
| GET | `/manage/{module}` | Paginated records. Query: `q`, `status`, `project_id`, `page`. Filters apply where the module has the corresponding field. |
| GET | `/manage/{module}/create` | Authorized creation form. |
| POST | `/manage/{module}` | Validate and create a record; redirect to listing. |
| GET | `/manage/{module}/{id}` | Read all permitted field values and related project links. Passwords are never displayed. |
| GET | `/manage/{module}/{id}/edit` | Authorized edit form; unavailable for payments. |
| PUT | `/manage/{module}/{id}` | Validate and update; unavailable for payments. |
| DELETE | `/manage/{module}/{id}` | Admin-only deletion with relation restrictions; payments cannot be deleted. |
| POST | `/manage/{module}/{id}/approval` | Manager/admin internal approval. `status=Approved` or `Rejected`; only expenses/invoices/contracts/change_orders. |
| GET | `/documents/{id}/download` | Authorized private attachment download as attachment. |
| GET | `/reports` | Authorized aggregate reports. `export=csv` downloads project costs. |
| GET | `/audit` | Administrator-only paginated audit history. |
| GET / PUT | `/settings` | Administrator-only business configuration. PUT accepts optional multipart `logo`. |
| GET | `/profile` | Account/password form. |
| GET | `/branding/logo` | Public logo bytes for the sign-in screen; returns 404 if absent. |
| GET | `/up` | Framework health endpoint; not a full database health check. |

All writes require a CSRF token and authenticated session except sign-in, which still requires CSRF. Field names, requiredness, maximums, allowed enums and relation tables are defined in `config/construction.php` and enforced by server validation. Documents require multipart `file` on creation; replacement is optional on update. User update password is optional; create password is required and hashed. Supplied fields outside the allowlist are not persisted.

## Responses and limits

The browser uses the POST/redirect/GET pattern, retaining validation errors and safe old values in the session. Requesting JSON can cause framework validation exceptions to return 422 JSON; this is not a stable external API contract. Guest web requests redirect to login. Unauthorized actions return 403, missing records 404, expired CSRF/session submissions 419, and excessive requests 429. Production 500 pages show no stack traces.

Sign-in has an email+IP failed-attempt limiter (5 attempts/60 seconds) plus an IP route limiter (15/minute). Authenticated web requests are limited to 180/minute; password changes to 5/minute; public logos to 60/minute. These are application safeguards, not a replacement for infrastructure DDoS protection.
