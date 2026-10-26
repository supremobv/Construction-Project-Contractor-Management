# Permissions and security operations

## Role matrix

This is a single-business **internal** workspace. Read permission grants visibility across projects, including base project budgets and contract values in general project records. Employees are not restricted to assigned projects. There is no external contractor/client portal or multi-tenant isolation.

| Capability | Administrator | Project manager | Accountant | Site supervisor | Viewer |
| --- | --- | --- | --- | --- | --- |
| Read projects, contractors, contracts and operational modules | Yes | Yes | Yes | Yes | Yes |
| Create/update projects, contractors, contracts, changes | Yes | Yes | No | No | No |
| Create/update tasks, logs, safety, materials, equipment, documents | Yes | Yes | No | Yes | No |
| Read expenses, invoices, payments and reports | Yes | Yes | Yes | No | No |
| Create expenses/invoices and record payments | Yes | No | Yes | No | No |
| Approve/reject expenses, invoices, contracts, changes | Yes | Yes | No | No | No |
| Update approved expenses/invoices | Yes, subject to paid-invoice lock | Expenses/invoices via approval decisions only | No | No | No |
| Edit or delete payments | No | No | No | No | No |
| Delete ordinary records | Yes, with relation constraints | No | No | No | No |
| Manage team, business settings, audit history | Yes | No | No | No | No |
| Change own password | Yes | Yes | Yes | Yes | Yes |

Administrators have broad control; keep their number small. Audit browsing is admin-only. Operational recent-activity feeds include only modules the user can read. User dropdowns deliberately expose employee names/id values to internal users who can assign work; passwords/session tokens are never included.

## Implemented controls

- Framework cookie session authentication; no public registration; no default production password.
- Strong password requirements and framework password hashing; administrator resets invalidate other database sessions. Self password changes require the current password.
- Session ID regeneration at sign-in/password changes; invalidation and CSRF rotation on logout; deactivated users blocked on their next request; session hash validation revokes stale sessions after password changes.
- CSRF middleware on state-changing web requests and sign-in; SameSite Lax/HttpOnly session cookies; encrypted database session payloads in the supplied configuration.
- Explicit role/module allowlists applied server-side to lists, details, forms, writes, approvals, downloads and exports.
- Bound SQL query values and validated relations/enums; no request-controlled table names outside the fixed registry.
- Blade escaping for business/user content, strong type/length/size validation, and tightly validated hex brand colors.
- Private uploads with random storage filenames, MIME validation, size limits, no executable/SVG/HTML file types, attachment download and no public storage route. Branding is deliberately public, limited to raster images.
- Security headers: frame denial, nosniff, same-origin CSP scripts/assets, object denial, restricted form/base sources, referrer policy and denied camera/microphone/geolocation. HSTS is sent for secure requests.
- Authentication and route rate limits; safe production errors; audit metadata for writes, decisions, authentication, downloads and exports.
- Database foreign keys, transactional writes/audit insertion, DECIMAL money storage, BCMath payment comparisons and invoice row locks. Approved accounting records have edit restrictions; paid invoices and payments are retained.
- CSV text cells are protected against common formula injection prefixes.

These controls reduce specific risks; they are not a certification or guarantee against all attacks. Framework controls were implemented using Laravel's [authentication documentation](https://laravel.com/docs/12.x/authentication) and [validation documentation](https://laravel.com/docs/12.x/validation).

## Production requirements

Use TLS; set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`. Configure trusted proxies only for known infrastructure so HTTPS/client IP handling is accurate. Serve only `public/`, deny dotfiles, and keep source/vendor/private storage unreachable from the web. Use a dedicated database account with access only to this schema, a strong password, and no remote public MySQL listener. Limit migration credentials separately when possible.

Keep `.env`, application keys, database credentials and backups out of Git and shared artifacts. Restrict writable directories to what Laravel requires. Apply PHP, Laravel, web server and dependency updates; run `composer audit` regularly. Rotate compromised credentials through a deliberate procedure. Never create local demo users on production; disable/delete known demo accounts before using a copied evaluation database.

Monitor authentication failures, unusual export/download volume and application errors. Retain/rotate logs under your business privacy policy. Audit rows are append-only through this application but a database administrator can alter them; regulated tamper evidence requires external immutable logging. Maintain encrypted backups and test recovery. Use infrastructure request limits/WAF/DDoS protection as appropriate for exposure.

## Explicit limitations and extension points

- No MFA, SSO, email verification or self-service email password recovery. Administrator resets require a trusted delivery process.
- No tenant/project membership ACLs. Do not give untrusted contractors/viewers access expecting project isolation.
- No malware scanner/content disarm for attachments, encrypted document vault, or granular document classification. Validate files with endpoint/scanning controls before production use.
- No payment gateway, bank transfer execution, e-signature, credit note/reversal, financial idempotency token. The app records internal payments; it does not send money. Check existing payments before retrying an uncertain submission.
- No pen test, independent security assessment, regulatory certification, or sustained adversarial/load test is implied by the automated checks.
- File and database storage are not a single atomic resource: successful updates clean old files and failed saves clean new files, but crash recovery may require orphan-file maintenance.
- Database session invalidation assumes the supplied database session driver. Changing drivers needs equivalent session-revocation behavior.

Business rules, retention, audit obligations and jurisdiction-specific construction requirements must be reviewed for the real deployment.
