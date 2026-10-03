---
title: Session Log 2026-09-21 Activity Budget Lifecycle
tags: [office, budget, workflow, receipts, testing]
status: active
---

# Activity approval and budget lifecycle

## Implemented

- SO final filing requires actual files in the required checklist slots. Ended proposals cannot be submitted or approved. Returned proposals retain existing uploads; submitted/approved proposals cannot be silently edited.
- OSO confirms document review; SDO saves SDG selections and assessment notes; OVCAA performs final approval. Transactions lock the activity and annual fund account. Wrong-desk and repeated endorsements are rejected. Each transition records actor, role, time and notes.
- Final approval reserves the activity allocation against its organization's academic-year account and creates one linked BudgetItem. Missing or insufficient funds prevent approval. The SO configures its organization’s annual fund account in Budget Utilization; OSO, SDO, and OVCAA can review balances but cannot edit the fund account. Lowering funds below allocations/spending is rejected.
- Once SO submits the paired AR + FR package, the SO upload and resubmit controls are locked. Opening either submitted report file from the OSO queue records `opened_at`/`opened_by` for the package; the lock remains through OSO review and is released only when OSO returns the package for revision.
- Allocated funds are not a second expense: available-to-allocate = original total minus allocations; cash = original total minus recorded spending; activity remaining = allocation minus spending.
- Receipts use permanent activity IDs, original filenames, uploader IDs, request keys and file hashes. A repeated upload does not debit twice. Currency retains cents; over-budget expenses are rejected. Post-event liquidation remains allowed.
- New receipt originals use private local storage; authenticated SO/OSO view/download routes serve them. Legacy originals retain their existing public storage location. SDO/OVCAA do not manually approve receipts.
- Failed chain confirmation leaves the receipt and its one-time expense debit saved as pending_seal. Retry only changes seal metadata. A blockchain seal is not OSO acceptance of AR/FR.
- Activity history includes receipts and office transitions with pagination. Each original is viewable/downloadable; a ZIP package includes originals and an expense CSV. Semester FR uses actual expense dates and matching receipts, not demo transactions. AR/FR package review remains SO→OSO only.
- Removed fictional audited/reconciled badges and demo financial data. Budget print and financial print are standalone report layouts. Student public budget summaries use activity IDs and approved activity totals without duplicating legacy category BudgetItems.

## Verification

- 34 targeted tests passed, 396 assertions: lifecycle, document visibility, report printing, budget UI, dashboard reporting, semester workflow, and Besu unit tests.
- Lifecycle tests cover all office handoffs, required uploads, return/resubmit retention, ended proposals, allocation limits, fund setup, cent-accurate debits, duplicate receipts, failed seal retry, file downloads, ZIP contents and semester totals. Fixtures are rolled back; chain success/failure is mocked for these tests.
- Chrome OSO checks: year filters change balances/receipts; activity selection shows its history; Midyear FR displayed three real receipt records totaling Php 14,800; no captured JavaScript errors. Narrow-view budget layout inspected, temporary viewport reset. Physical phone camera capture was not retested.
- Read-only live Besu check: reachable, four validators, three peers. No new live financial transaction was broadcast for testing. Current ten stored receipts are linked and their originals exist; there were no pending seals.

## Operational limitations / setup

- No 2026–2027 organization fund accounts existed at verification. Enter authorized real totals before new final approvals. Do not manufacture balances or copy last year's unspent funds automatically.
- Existing legacy spending is preserved as opening_spent; it does not create missing receipts or imply all historical expenses are supported by originals. Legacy seeded records are not proof of a completed real approval process.
- SO accounts currently use the existing shared role model, without account-to-organization ownership enforcement. Per-organization account isolation remains a separate access-control requirement before multi-organization production use.
- Blockchain confirmation recovery prevents a second budget charge; an uncertain network timeout is not a guarantee of exactly one blockchain transaction.
- No claim of whole-system production certification or a physical-device upload test.

## Backup and operations

- Pre-change MySQL dump: `storage/app/private/backups/activity-budget-20260920-192052/database.sql` (2,049,488 bytes).
- Applied migrations `2026_09_21_000005_link_activity_budget_ledger` and `2026_09_21_000006_add_receipt_storage_disk`.
- Read-only check: `php scripts/check-activity-budget.php`.

Related: [[Budget Utilization and OCR Receipts]], [[Activity Proposal Approval Pipeline]], [[Semester AR and FR Submission Workflow]].

## Calendar-year chart correction

- Dashboard submission trends and Analytics monthly/cumulative charts now display January through December, not a rolling 12-month window or August–July academic year.
- Dashboard and Analytics year selectors explicitly show calendar years, derive options from stored records plus recent years, and default/reset to the current year. Semester/month/scope filters still work within that calendar year; empty months stay visible.
- All Calendar Years groups matching records by calendar month across all years; it no longer silently drops records outside the latest rolling window.
- Server-side month generation begins on January 1 to prevent month-end overflow (for example October 31 skipping February during subtraction). Year filtering uses the actual date year rather than the academic-year ending year.
- AR/FR and Budget Utilization academic-year/semester reporting periods remain unchanged.
- Verification: three Node tests execute the actual chart aggregation functions; two PHP feature tests (29 assertions) cover month-end/year boundaries and rendered chart pages for SO, OSO, SDO and OVCAA. All passed; Blade cache compilation and PHP lint passed.

## OSO live dashboard data pass

- The OSO dashboard summary is now built from the real `org_activities`, `student_organizations`, `org_renewal_submissions`, `org_report_statuses`, and `tosa_applicants` tables. Activity rows carry the stored organization alias, college, scope, workflow bucket, submission date, calendar year, semester, month, and real detail URL.
- The transaction-type chart now counts Activity Proposals, Renewal packets, FR, AR, and TOSA records from their respective tables. Processing-time bars only appear when stored submitted/reviewed timestamps exist; open or draft records do not receive invented durations.
- OSO pending totals include live proposal review/revision rows, submitted renewal packets, complete AR + FR packages in `oso_review`, and pending TOSA applicants. The OSO dashboard no longer uses fabricated approval counts, synthetic chart rows, or the old rejection slice that the activity workflow does not support.
- Filters apply to the embedded database-backed rows. Calendar charts remain January–December, the organization subtitle uses the current academic year, and empty database states render zero/empty-state values rather than demo balances.
- Verification: `DashboardReportingTest` passed 14 tests / 178 assertions; `CalendarChartTest` plus `DashboardReportingTest` passed 16 tests / 207 assertions; the chart Node tests passed 3/3; PHP lint and Blade cache compilation passed.

## SO shared receipt sets and item inputs

- Each SO submission has one shared **Receipts / Supporting Documents** upload area. It accepts up to three JPG, PNG, WebP, or PDF files, with a 5 MB limit per file; gallery selection, drag-and-drop, and camera capture all append to that shared set.
- **Add item** creates an input-only row. It never asks the SO to upload the receipt again. The SO enters one row for each purchased item shown on the shared receipts, even when one receipt contains many line items.
- The server stores the uploaded files once and references the same attachment set from each item row. Each row still becomes one `ExpenseReceiptReview` and one quantity × unit-cost ledger debit; the receipt is never charged once per item or duplicated on disk.
- Receipt History shows the shared originals once, while the itemized history and compilation CSV identify every encoded item. The ZIP export de-duplicates shared files and includes the register mapping each item to the shared receipt files.
- The active path is manual upload plus manual item entry. OCR and DOCX preflight are not called; legacy scan metadata remains readable only when an older client explicitly sends `receipt_scan_id`.
- Migrations applied: `2026_09_21_000011_add_receipt_attachments_to_expense_receipt_reviews` and `2026_09_21_000012_allow_shared_receipt_files_across_expense_items`.
- Verification: `BudgetUtilizationTest` passed 8 tests / 47 assertions; `ActivityBudgetLifecycleTest` passed 11 tests / 197 assertions, including a shared two-file receipt set used by multiple item rows. PHP lint, Blade cache compilation, JavaScript syntax check, and diff whitespace checks passed.
