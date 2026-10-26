# User manual

## Sign in and your account

Open the local workspace URL and enter the email/password supplied by your administrator. There is no public registration. Use Account & password to change your own password; enter the current password and confirm a strong new one. Other database sessions are removed when passwords are changed. For a forgotten password, contact an administrator, who can set a replacement through Team & access. Deliver replacements through an appropriate private channel and change them immediately after sign-in. Self-service email resets and MFA are not included.

## First business setup

1. As administrator, open **Business settings**. Set the business name, email, phone, address, display currency, logo and light accent color. PNG, JPG and WebP logos are accepted up to 2 MB. Keep a logo blank to retain it.
2. Open **Team & access**, add employees and choose the least privileged suitable role. Use unique emails and strong passwords. Active = 1 allows sign-in; inactive = 0 blocks sign-in and terminates access on the next request. You cannot disable/demote/delete your own administrator account.
3. Add contractors, then projects, contracts and tasks. All employees are internal users; a contractor directory entry has no login.

## Dashboard and navigation

The overview shows active projects/contractors, open and overdue tasks, recent permitted activity and financial totals for authorized roles. Task deadlines use the configured application date. Click a navigation item to open a module, use search/status/project filters, and select a record's name to see all details. Edit is shown only when your role permits it. Use the menu button on smaller screens. Lists show 12 records per page.

## Projects and delivery

- **Projects:** enter a unique code, name, client, location, start/end dates and base budget. End date cannot precede start. Select a manager and update progress from 0–100 manually. Project status and progress are independent; record completion deliberately. The project detail page links to all related modules and displays its scheduled tasks/milestones.
- **Contractors:** enter trade, contact details, license number/expiry and review status. Expiry is a register field; staff must review it. No automated licensing checks are performed.
- **Contracts:** connect the contractor to a project, define scope, dates, value and status. Approval is an internal record, not an electronic signature.
- **Tasks & milestones:** connect a project, optional contractor/employee, priority, deadline and status. Set milestone = Yes for a delivery checkpoint. Mark work Done after verification. Overdue means the due date passed and the task is not Done.
- **Daily site logs:** record date, weather, workers, hours (0–24), work completed and issues. This is a site journal, not individual payroll or attendance.
- **Safety & issues:** describe the incident/issue, severity, investigation status and corrective action. Follow applicable safety procedures separately; recording a row is not a regulatory incident submission.
- **Change orders:** record additional budget amount, schedule impact in days, reason and approval status. Approved amounts appear in adjusted budgets. Date impacts are informational; revise the project's end date manually after approval. The MVP records additions, not negative budget credits.

## Resources and documents

Materials track quantities, units, supplier, unit cost and requested/ordered/delivered/used status. Equipment tracks a serial number, assigned project, usage status, daily cost and next service date. These are registers, not stock movement or purchasing engines. Enter actual incurred costs separately as expenses.

Documents connect to a project with a category and version label. Upload PDF, JPG, PNG, WebP, DOCX, XLSX or TXT up to 10 MB. Version labels are manual; uploading a replacement removes the previous file after saving, so create a separate document record to retain previous versions. Click the document name to view metadata or Download for its attachment. Only signed-in internal users with document access can download files. Treat attachments as untrusted and use your endpoint protection.

## Financial workflow

1. An accountant or administrator enters an expense or contractor invoice as Draft or Submitted.
2. A manager/administrator reviews the record and chooses Approve or Reject in its row. Approval decisions are audited. An approved expense/invoice is locked against accountant editing.
3. For an approved contractor invoice, an accountant/administrator records a payment with date, amount, method and reference. The amount must be positive and no greater than the remaining invoice balance. Partial payments are supported. Each payment reference must be unique, and invoice names must be unique per contractor. Open invoice details to see the outstanding balance.
4. Payments are immutable and cannot be deleted through the application. An invoice with payments cannot be edited, rejected or deleted. Financial corrections require a reviewed accounting/reconciliation process; reversal/credit-note workflows are outside this MVP.
5. Open Reports to see adjusted budgets, approved expenses, invoice payables, payments and remaining budgets. Export CSV for spreadsheet analysis. Browser printing can be used for a printable report.

Expense records and contractor invoice payments are separate cost streams. **Do not enter an invoice payment again as an expense.** Remaining budget subtracts approved expenses and recorded contractor payments; approved unpaid invoices appear as payables. Currency changes affect labels only; no exchange-rate conversion occurs. Reports are not a statutory accounting ledger.

## Audit, deletion and errors

Administrators can view sign-ins/sign-outs, record creation/update/deletion, approvals, downloads and exports in Audit trail, including actor, time and IP. There is no audit edit/delete screen. This log records event metadata, not full before/after values.

Only administrators can delete ordinary records. The system asks for confirmation; deletions are permanent. Linked records are protected by foreign keys and will show a friendly message instead. Prefer changing status to Completed, Cancelled, Suspended or Retired when appropriate. Backups are the recovery mechanism.

Validation errors appear above the form and beside affected fields. Correct them and save again; passwords/files must be re-entered after validation. A 403 means access is restricted, 419 means refresh/sign-in is needed, 429 means wait briefly, and 500 means contact your administrator. Do not repeatedly submit a payment when unsure whether it saved: check the Payments list and invoice balance first.
