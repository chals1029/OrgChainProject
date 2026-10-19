---
title: Semester AR and FR Submission Workflow
tags: [workflow, office, reports, ar, fr]
status: active
---

# Semester AR and FR Submission Workflow

## Purpose and permissions

The Accomplishment Report (AR) and Financial Report (FR) have **independent** organization/academic-year/semester lifecycles. Either can be prepared, submitted, reviewed, returned, rejected or verified and archived without waiting for the other. They are not a jointly accepted pair.

SO prepares only its assigned organization's reports. OSO makes each report's final decision. SDO, OVCAA and OC do not review semester reports and cannot open their report pages or private document endpoints; their activity-proposal responsibilities remain separate.

## Preparation and submission

1. Choose the academic year and semester; period selectors refresh automatically.
2. **AR:** **Create activity report** stages a completed DOC/DOCX/PDF up to 20 MB as a private draft. **Submit AR to OSO** is separate. Existing native activity-report drafts remain editable where permitted; their generated packets retain completeness/evidence checks. An independent uploaded completed AR is not blocked by unrelated native draft evidence.
3. **FR:** **Current Reports** appears before **Financial Report History**. Empty states offer **Create report**; history search and year/semester filters remain available. New FR creation uses the completed official XLSX workbook (up to 10 MB), saved privately. Preview the exact latest saved file before submission; replacing it requires a new preview. Viewing an unsupported legacy Word file does not satisfy that gate.
4. Confirm submission of the selected report. The server requires a readable current file and an open submission window, changes that report alone to `oso_review`, and immediately locks its editing/resubmission.

```text
AR draft --> submit AR --> OSO review --> verify and archive AR
                            |     |
                            |     +--> reject AR (locked)
                            +--> return AR --> revise --> resubmit AR (locked)

FR draft --> preview exact FR --> submit FR --> OSO review
                                                | verify and archive FR
                                                | reject FR (locked)
                                                + return FR --> revise / preview --> resubmit FR (locked)
```

## OSO review and archival

- `/office-desk/reports` is the OSO **AR & FR Reports** desk. The directory retains available report links when either AR or FR is submitted.
- OSO reviews only the current selected report in `oso_review`, with **accept**, **return** or **reject**. Return/reject require a note.
- Return sets that report to `returned` with `returned_to=so`, permitting revision and resubmission. Rejection is final for SO editing/resubmission; it is not a revision unlock.
- Acceptance verifies and archives **that report's** readable latest submitted document in one transaction, preserves the private `local` disk reference/original filename, and changes its status to `archived`.
- Opening a submitted document records its opened timestamp/reviewer; opening a file is not an acceptance or an unlock.
- Manual [[Document Archive and Compliance Repository|Archive]] upload does not submit or decide a report.

## Submission controls: separate from report review locks

OSO can lock or reopen **AR and FR separately** for one selected academic year and semester, applying to **all organizations**.

- **Lock AR/FR submissions** opens the existing report-specific dedicated dialog with the period and all-organizations scope. Only **Yes, lock submissions** posts the lock.
- Cancel, Escape or backdrop dismissal changes nothing. No second shared confirmation is layered on that dedicated dialog.
- **Reopen** also requires confirmation. It changes neither the other report type nor a neighboring period, and does not unlock an already submitted/final report for editing.
- While filing is locked, SO may still prepare, preview and download eligible drafts; existing submitted reports remain reviewable. The window blocks submission/resubmission, not read-only access.
- Other report submissions/reviews use shared business-action confirmations. Cancellation preserves draft/file state; selection, previews, downloads and navigation do not prompt.

## Persistence

- `org_report_statuses`: type-specific state, period, submission/open/review timestamps, reviewer and archive folder. A `batch_key` does **not** imply an AR/FR pair; each submission gets its own key.
- `org_report_documents`: staged private originals and optional native/report workbook summaries.
- `archive_folders` / `archive_documents`: preserved report records on OSO acceptance; the source file is not moved to public storage.
- Report submission-window state is separate from each organization's review status.

## Current routes

| Method | Route | Purpose |
| --- | --- | --- |
| POST | `/office-desk/reports/{reportType}/documents` | SO stages an AR/FR document |
| POST | `/office-desk/reports/{reportType}/submit` | SO submits only `ar` or `fr` |
| POST | `/office-desk/reports/{reportType}/review` | OSO decides only `ar` or `fr` |
| POST | `/office-desk/reports/{reportType}/submission-lock` | OSO locks/reopens that type and period |
| GET | `/office-desk/reports/documents/{document}/view` | SO/OSO authorized original preview; `download=1` downloads |
| GET | `/office-desk/reports/documents/{document}/workbook` | OSO workbook preview |
| POST | `/office-desk/financial-report/reports` | SO creates current XLSX FR |
| GET | `/office-desk/financial-report/reports/{document}/preview` | SO exact-file FR preview |
| POST | `/office-desk/financial-report/reports/{document}/submit` | SO submits that current FR |

The former `/reports/semester/submit` and `/reports/semester/review` pair endpoints are not current routes.

Related: [[Multi-Tier Office Roles SO OSO SDO OVCAA]] · [[Budget Utilization and OCR Receipts]] · [[Document Archive and Compliance Repository]] · [[Office Portal API and Action Endpoints]] · [[Web and Portal Route Map]]

## Source evidence

`app/Services/SemesterReportService.php`; `ReportSubmissionWindowService.php`; `OfficePortalController::storeReportDocument()` / `submitSemesterReport()` / `reviewSemesterReport()`; `SoFinancialReportController`; `ReportSubmissionWindowController`; `resources/views/org/semester-reports.blade.php`; `routes/web.php`; current README/CHANGELOG.
