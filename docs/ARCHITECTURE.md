# Architecture and customization

## Stack

Laravel 12, PHP 8.2+, traditional MySQL 8.0+, Blade templates, local CSS and small progressive JavaScript enhancements. Server rendering avoids a separate SPA API/authentication stack and JavaScript bundle build. The application uses framework session authentication, CSRF protection, validation, Query Builder parameter binding, storage and transactions.

## Source map

| Path inside `app/` | Responsibility |
| --- | --- |
| `config/construction.php` | Explicit module/field allowlist, option values and role permissions. |
| `database/migrations/` | Versioned schema, decimal money columns, indexes and restrictive relations. |
| `database/seeders/DatabaseSeeder.php` | Initial business identity only. |
| `database/seeders/DemoSeeder.php` | Fictional local demonstration records/accounts. |
| `app/Support/Access.php` | Central server-side permission checks and audit insertion. |
| `app/Http/Controllers/AuthController.php` | Sign-in throttling, session rotation, logout, password changes. |
| `app/Http/Controllers/WorkspaceController.php` | Authorized CRUD, files, approvals, payment transactions, reports and settings. |
| `app/Http/Middleware/SecurityHeaders.php` | Inactive-account revocation, security headers and content policy. |
| `routes/web.php` | Session-authenticated web routes; only login/logo/assets are public. |
| `routes/console.php` | Initial administrator command. |
| `resources/views/` | Layout, overview, module forms/details/lists, settings, reports and error pages. |
| `public/css/app.css`, `public/js/app.js` | Responsive UI, menu, confirmation and submission feedback. |
| `public/logo.svg` | Original editable sample brand mark. |
| `storage/app/private/` | Private document and business-logo storage, excluded from Git. |
| `tests/Feature/WorkspaceTest.php` | Permissions, workflows, views, validation, balance, upload and authentication checks. |

## Data model

Projects belong to a business workspace and optionally reference an employee manager. Contracts link a project and contractor. Tasks reference a project and optional employee/contractor. Expenses, invoices, daily logs, safety issues, changes, materials and documents reference a project; equipment assignment is optional. Payments reference an invoice and derive its project and contractor through that relation. Restrictive foreign keys stop deletion of referenced parents. User records hold one role and active status. Business settings has one application-managed row. Audit entries retain an actor label even when a user is removed.

Money is stored as `DECIMAL(15,2)`. Payments use BCMath for comparison/subtraction and MySQL transaction row locks on the parent invoice. Invoice modifications, approval changes and deletions use the same invoice lock so a paid invoice cannot be changed while another request creates a payment. Reports use aggregate subqueries rather than one query per project. Lists paginate at 12 records, audits at 25. Relationship pickers load name/id options for ease of use; very large employee/project/contractor catalogs would benefit from authorized autocomplete endpoints.

## Customization

Business administrators change identity through Settings, with no source edits. Currency is one display currency per business, not multicurrency accounting. Role assignments are changed in Team & access. Permission definitions are code-controlled in `config/construction.php`; adding a role requires a code change and tests, not a role editor UI.

Use the registry to change labels, permitted enum values, field hints/types and role capabilities. **New fields/tables require a new schema migration.** Do not edit an applied migration or assume editing the registry modifies a live schema. The initial migration uses the registry to generate columns, so future schema evolution should freeze historical definitions before changing existing fields. Keep a tested database upgrade path and back up before deployment.

Put domain-specific business invariants in controller/service logic, with authorization enforced on every route. The registry pattern is an MVP tradeoff to provide consistent validated forms for multiple registers; as workflows grow, split modules into dedicated controllers, Form Requests, models and services. Avoid permitting request values to choose arbitrary table names, sort columns or fields.

CSS variables control accents and a shared layout controls branding. The illustration is an inline, original SVG and can be edited without an image service. Asset URLs are same-origin; the content security policy blocks external fonts/scripts. Any integration requiring external assets must receive a narrowly scoped CSP change and security review.

## Performance and operations

No background polling, external image fetches or large frontend libraries are required. Assets can be cached by Apache/Nginx; authenticated HTML is `no-store`. Run PHP OPcache and cache Laravel configuration/routes/views for production. Use MySQL indexes and monitor query latency. Dashboard totals are live queries; larger deployments may use short-lived authorized aggregate caches with invalidation. Current verification covers functional behavior on a small local sample, not a production load or concurrency benchmark.
