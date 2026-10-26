# Forma — Construction Project & Contractor Management

A local MySQL application built with Laravel 12 and PHP 8.2+, with a responsive server-rendered interface. No Node build, external CDN, or paid service is required. The included brand and demo data are fictional and replaceable.

## Start on this computer

MySQL database `forma_construction` has been created and populated with sample data. Start MySQL in Laragon, then run `./start.ps1` in this folder and open <http://127.0.0.1:8000>.

Local sample administrator: **admin@forma.test** / **BuildTogether!2026**. Other demo accounts use the same sample password: `manager@forma.test`, `accountant@forma.test`, `supervisor@forma.test`, `viewer@forma.test`.

Demo accounts are for local evaluation. Use a separate, empty schema and create your own administrator for real business data.

## Fresh installation

1. Install/start Laragon with PHP 8.2+ and MySQL 8.0+; Composer 2 must be available in the terminal. PHP needs PDO MySQL, BCMath, Fileinfo, GD, Mbstring, OpenSSL, XML, Zip and the standard Laravel extensions.
2. From Laragon Terminal / PowerShell, run `./setup.ps1 -Demo` for an evaluation workspace, or `./setup.ps1` for a clean business installation. You can run scripts with `powershell -ExecutionPolicy Bypass -File .\setup.ps1 -Demo` if your current policy blocks local scripts.
3. If your MySQL credentials differ from local defaults, copy `app/.env.example` to `app/.env` and edit `DB_*` before setup. The setup preserves an existing `.env` and never drops tables. If the account cannot create databases, create an empty schema manually first.
4. For a clean installation, run `cd app`, then `php artisan workspace:admin owner@example.com`. Enter a strong password at the hidden prompt. There is no default account in the normal seeder.
5. Run `./start.ps1` from the root folder. The development server binds only to localhost.

For a Laragon Apache or Nginx virtual host, the document root **must be `app/public`**, never this project root or `app`. Configure URL rewriting to `public/index.php` and set `APP_URL` to the local host URL.

## Included MVP

- Dashboard with live project, contractor, task and cost summaries.
- Projects: clients, locations, budgets, progress, schedules, manager assignment and linked project workspace.
- Contractor directory: contacts, trades, licenses, expiry and status.
- Contracts, tasks, milestones, daily site logs, safety issues and change orders.
- Material and equipment registers, supplier information and service dates.
- Private documents with categories, version labels, uploads and authorized downloads.
- Expenses and contractor invoices with manager approval; partial payments with exact decimal balance checks and immutable payment records.
- Financial reports, unpaid invoice totals and CSV export with spreadsheet formula protection.
- Five server-enforced roles, team administration, account deactivation, password changes and audit logs.
- Editable business name, contact details, logo, currency and accent color.
- Search, status/project filters, pagination, empty states and accessible validation messages.

## Documentation

- [Laragon and deployment setup](docs/SETUP.md)
- [User manual](docs/USER-MANUAL.md)
- [Architecture and customization](docs/ARCHITECTURE.md)
- [Routes and integration contract](docs/ROUTES.md)
- [Permissions, security and operational guidance](docs/SECURITY.md)
- [Scope, limitations and verification](docs/ACCEPTANCE.md)

## Checks

Inside `app`, run `php artisan test --compact` for isolated in-memory tests. MySQL integration tests use `composer test:mysql` and a dedicated **`forma_construction_test`** database, which must exist first. Tests refuse non-SQLite databases whose name does not end in `_test`. Never point the test configuration at a working database.

Run `composer audit` to check installed PHP dependency advisories. `php artisan view:cache` checks Blade compilation. See `docs/ACCEPTANCE.md` for recorded results and limits.

## MVP boundaries

This is a single-business internal workspace: authorized read access applies across projects. Contractor records do not create external portal logins. It is not a full ERP, payroll system, BIM viewer, procurement engine, tax accounting package or legal compliance certification. Security controls are implemented and tested, but immunity from every attack is not a promise any application can make. Production requires the operational steps in `docs/SECURITY.md` and a review for your deployment and business rules.
