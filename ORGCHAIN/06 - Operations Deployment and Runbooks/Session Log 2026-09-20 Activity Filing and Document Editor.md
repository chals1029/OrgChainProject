---
title: Session Log 2026-09-20 Activity Filing and Document Preview
tags: [session, changelog, activities, docx, compliance]
created: 2026-09-20
status: superseded
---

# Session Log — 2026-09-20: Activity Filing and Document Preview

## Shipped

### Type-aware activity filing

- Activity creation now supports **In-Campus** and **Local Off-Campus** as real `activity_type` values.
- The selector changes the visible official checklist, template links, document pack, and conditional questions without a page reload.
- Activities require an allocated budget greater than zero when saved.
- Draft saves do not require the final checklist packet; final submit validates required pre-activity uploads for the selected type.
- Conditional requirements are enforced only when selected. During-activity and after-activity documents stay available as later uploads.
- Keyed uploads and bulk `supporting_documents[]` uploads are stored on the public disk under the activity-specific in-campus/off-campus folder.
- Final submissions create pending `activity_compliance_docs` rows for active requirements.

### Real DOCX preview

- Official in-campus and local off-campus template links no longer navigate directly to a download response when the user clicks Preview.
- Added a self-hosted DOCX/PDF/image preview modal using the existing `jszip` and `docx-preview` vendor assets. Download stays explicit in the modal footer.
- Supported DOCX files render with their real tables, headers, typography, and page structure in the preview modal. Legacy binary `.doc` files show a download-only explanation instead of triggering an unexpected download.

## Editor removal

The in-app editor was removed after verification because it did not preserve the official Word layouts well enough for this workflow. The create/edit form now keeps only the official template preview, explicit original-file download, checklist uploads, and activity filing fields. The editor UI, editor save handling, generated DOCX export route, editor test, and PHPWord dependency were removed. Existing nullable `*_html` database columns remain untouched for compatibility and are no longer written by this flow.

## Verification

- `php -l` passed for the controller and routes.
- `php artisan view:cache` passed.
- `php artisan test --without-tty` passed after the editor removal: **37 tests, 143 assertions**.
- Chrome/ngrok verification covered the real in-campus and local off-campus DOCX previews, modal behavior without navigation/download, type switching, and both requirement sets.

## Documentation updated

- [[Activity Proposal Approval Pipeline]]
- [[In-Campus Activity Proposal Checklist]]
- [[Local Off-Campus Compliance and CHED Workflow]]
- [[Model - InCampusActivitySubmission and OrgActivity]]
- [[Office Portal API and Action Endpoints]]
- [[Web and Portal Route Map]]
- [[Tech Stack and Dependencies]]

## Final removal verification

- The editor-specific route, controller methods, Blade card/scripts, editor test, and active documentation references are absent.
- The official template preview route remains available with `preview=1`, and the explicit download path remains unchanged.
- `php -l` passed for the controller and routes, and `php artisan view:cache` passed after the removal.

## Follow-up: student-to-OSO document synchronization

- The SO edit link now reuses the existing `InCampusActivitySubmission`; saving an edited activity no longer creates a fresh submission that hides the previous attachment set.
- Keyed checklist uploads and bulk `supporting_documents[]` uploads are synchronized into `activity_compliance_docs` during draft saves and final submission, so OSO can see documents before the filing is finalized.
- The OSO activity dossier also projects legacy attachment JSON when a compliance row is missing, making existing uploads visible without a destructive data migration.
- Review rows preserve the actual uploaded filename extension, and replacement uploads reset the document to `pending` for OSO review.
- Follow-up verification passed: **37 tests, 143 assertions**, PHP syntax checks, and Blade view caching.

## Follow-up: real TOSA and report source templates

- Added the supplied original source files under `TOSA/` and `Reports/` instead of serving generated Word scaffolds for these workflows.
- The OSO Official Template Documents page now exposes the real `TOSA Application Form 2025.docx`, `Accomplishment & Financial Report.docx`, `Particulars of the Accomplishments.docx`, and `Written Explanation.docx` files.
- The TOSA workspace, Financial Report page, and Accomplishment Report page each provide the relevant official source download.
- Source-template downloads preserve the original file content, filename, and MIME type; DOCX files can therefore use the existing in-page Word preview engine.
- The TOSA ZIP also remains represented in the repository with its supporting PDF, DOC, and XLSX files for future package/download wiring.
- Verification after this follow-up: source download responses returned `BinaryFileResponse` with the DOCX MIME type, PHP syntax passed, and Blade view caching passed.

## Follow-up: OSO submitted-document preview

- Replaced the OSO activity-detail `Read` link with a real in-page `Preview` action. It no longer navigates directly to a storage response that Chrome treats as a download.
- Added a centered preview modal for submitted PDF, image, and DOCX files. DOCX files render from the original uploaded blob with the existing self-hosted preview assets; the original download remains available as an explicit modal action.
- Legacy binary `.doc` uploads now show a clear download-only explanation instead of silently downloading when a reviewer tries to read them.
- Verified the real `CICS SPORTFEST` activity in Chrome: the Programme DOCX opened inside the modal with its document text rendered, and the browser console reported no errors.
- Final verification: `php artisan view:cache`, `php artisan test --without-tty` (**37 tests, 143 assertions**), and `git diff --check` passed.

## Follow-up: WPCF legacy `.doc` preview

- Confirmed `Waste-Policy-Compliance-Form-2026 (1).doc` is a genuine binary Word 97-2003 document, not a DOCX file with the wrong extension.
- Added a local PDF preview rendering for the official WPCF template. Preview requests return the PDF inline; the Download original action still returns the official `.doc` attachment unchanged.
- The converted preview was visually rendered and checked for clipping or unreadable content before wiring it to the activity-template preview route.
- Endpoint verification confirmed `preview=1` returns `application/pdf` and the normal download returns the original `.doc` filename/disposition. PHP syntax, Blade cache, and all **37 tests / 143 assertions** passed.

## Follow-up: remove participant panel from activity review

- Removed the `Registered Participants` card from the activity detail/review page. OSO, SDO, OVCAA, and student-organization reviewers now finish the dossier view after the compliance-document guidance panel.
- Registration records and backend data remain unchanged; this is a presentation-only removal so future RSVP functionality is not affected.

## Follow-up: preserve activity-upload filenames and keep previews inline

- Added the authenticated `office.activities.attachments.file` endpoint for submitted activity documents.
- OSO activity previews now fetch the document through an inline response instead of exposing Laravel's random storage key directly.
- Explicit downloads use the original filename captured during upload, while preview requests remain inline and do not create a browser download-history entry.
- Updated both the activity editor and activity review page to use separate preview and download URLs.
- Existing stored uploads remain intact; their random physical storage keys are no longer shown to users when the original filename metadata exists.
- Verification: the WPCF upload returned `inline; filename="Waste-Policy-Compliance-Form-2026 (1).doc"` for preview and `attachment; filename="Waste-Policy-Compliance-Form-2026 (1).doc"` for explicit download; PHP syntax, Blade cache, **37 tests / 143 assertions**, and `git diff --check` passed.

## Follow-up: real TOSA DOCX on the student portal

- Added the student-authenticated `portal.tosa.template` route for the supplied `TOSA/TOSA Application Form 2025.docx` source file.
- The student TOSA panel now shows one official-form card with an inline DOCX preview and an explicit DOCX download using the original filename.
- The upload workflow remains PDF-only for completed/signed submissions; the DOCX is the official form students use before exporting or scanning their completed application dossier.
- Added the self-hosted DOCX preview assets and responsive modal states for loading, success, error, close, and explicit download.
- Verification: `npm run build`, PHP syntax checks, Blade cache, and **38 tests / 157 assertions** passed. The portal render includes the official-form actions, and the preview/download endpoints returned the real DOCX MIME type with inline and attachment dispositions respectively.

## Follow-up: mobile-friendly SO budget receipt capture

- Kept the existing server-backed SO receipt flow (`POST /office-desk/budget-utilization/receipt-reviews`) and its file validation, OCR review, and receipt confirmation rules unchanged.
- Constrained the budget page’s outer container so it cannot expand beyond the phone viewport; this removed the horizontal clipping that affected the upload card on mobile.
- Reworked the receipt capture UI for narrow screens: full-width Gallery and Camera actions, one-column expense fields with mobile-safe touch/input sizing, a contained receipt preview, stacked submit actions, and a phone-sized live-camera dialog that respects the viewport.
- Verified the SO page at a 390px mobile viewport: page width matched the viewport with no horizontal overflow, the gallery action opened the file chooser, the camera input retained `capture="environment"`, the receipt form posted to the server route, and no page/console errors were reported.
- Verification: `php artisan view:cache`, `php artisan test --filter=BudgetUtilizationTest` (**4 tests, 17 assertions**), the full suite (**39 tests, 164 assertions**), `npm run build`, `git diff --check`, and mobile screenshot review passed.

## Follow-up: complete monthly Submission Volume Trend report

- Confirmed the live OSO dashboard was dropping zero-activity months: the trend was only showing `Mar, Apr, May, Jun, Jul` with `2, 5, 3, 5, 7`, so the report could not show the earlier zero months or a complete academic-year sequence.
- Changed the trend aggregation to always render 12 month slots for the selected academic year, August through July, with zero values preserved. The fallback dashboard chart payload also now covers 12 months instead of six.
- Added month-to-month reporting text and red point markers for declines; the live sample now reports `Largest month-to-month drop: May ↓2 (5 → 3)` while retaining the complete `Aug` through `Jul` axis.
- Added mobile-safe width constraints to the OSO dashboard grid so the chart and summary remain inside the phone card.
- Verification: live OSO dashboard checked at desktop and 390px mobile sizes, complete 12-month data and drop summary confirmed with no page errors; `php -l`, Blade cache, `git diff --check`, and the full suite (**40 tests, 169 assertions**) passed.

## Follow-up: remove duplicate office confirmation banners

- Confirmed the activity review screenshot was showing the same success confirmation twice: once from the shared office layout and once from page-level success markup.
- Removed the redundant page-level success banners from Activities, Create Activity, Budget Utilization, and Renewal. The shared layout remains the single source for success/error flash messages; page-specific validation errors remain available.
- Added a regression test covering all four office pages so a flash confirmation renders exactly once.
- Verification: full suite (**41 tests, 177 assertions**) passed, Blade cache passed, and `git diff --check` completed without errors.

## Follow-up: make reporting filters data-aware across every desk

- Repaired the shared Office Analytics filter pipeline used by SO, OSO, SDO, and OVCAA. Academic year, semester, month, scope, and organization changes now recalculate the KPIs, college performance table, scope cards, ranked lists, activity rows, and both charts from one filtered activity dataset.
- Added normalized activity period metadata (`date_key`, academic year, semester, month, scope, organization, and status) to the server payload so the browser does not guess from display labels.
- Replaced Budget Utilization’s label-only Year/Period behavior with real filtering and consolidated period rollups. A selected period now changes the budget totals, scope donut, bar chart, expense rows, documents, and timeline; unsupported periods show an explicit empty state instead of unrelated data.
- Made Financial Report Year/Semester controls filter ledger transactions and supporting documents, recalculate the ledger net total, update the report period, preserve flow/search filters, and show a no-records state when appropriate.
- Made Accomplishment Report period controls retain live database rows across changes and filter them by the same academic-year/semester rules. Unsupported combinations no longer silently fall back to the default report.
- Confirmed the student department and status selectors remain bound to the portal query and preserve their selected values after filtering.
- Added a cross-desk regression test covering all four office roles plus the authenticated student filter surface.
- Verification: **43 tests, 246 assertions** passed; `php -l app/Http/Controllers/OfficePortalController.php` passed; Blade view cache passed. Chrome had already confirmed the repaired Analytics scope filter changed the live KPI, table, scope totals, and chart period badge.

## Follow-up: stop repeated workflow endorsements

- The activity detail action bar now checks the current workflow stage against the signed-in desk before rendering an active endorsement form. After OSO advances an activity to SDO review, OSO sees a disabled `Already endorsed` state instead of a working button; the same rule applies to SDO and OVCAA hand-offs.
- Added submit-time button disabling to prevent accidental double-clicks while the workflow request is being processed.
- Tightened `OrgWorkflowService::canAct()` to return true only when the role has a real transition from the current status. The advance endpoint performs the same check, so stale browser tabs cannot repeat an endorsement through a direct POST.
- Verified the CICS SPORTFEST record remains mapped to `CICS-SC` / College of Informatics and Computing Sciences. No standalone `CS` or `IT` organization option exists in the current organization source or database; no unrelated organization was deleted.
- Verification: **44 tests, 256 assertions** passed for the targeted workflow regression, PHP syntax and Blade cache passed.

## Follow-up: make the Calendar Off-Campus scope functional

- The Calendar already displayed an Off-Campus pill, but calendar events did not carry their database `activity_scope`, so off-campus records were classified as in-campus in the UI.
- Added normalized `scope_key` metadata to live and fallback calendar events. The real local off-campus records, including `Leadership Summit 2026`, now render with the blue Off-Campus marker.
- The All / In-Campus / Off-Campus controls now filter individual calendar event pills, hide nonmatching upcoming-list entries, update the `+N more` count, and limit day modals to the selected scope. The upcoming list always includes off-campus events even when they fall outside the first eight mixed-scope entries.
- Added a calendar regression test for the September off-campus payload. Verification: **45 tests, 262 assertions** passed, PHP syntax passed, Blade view cache passed, `npm run build` passed, and `git diff --check` passed.

## Follow-up: remove duplicate TOSA applicant presentation

- Confirmed the TOSA database contained one record for the reported Charles applicant. The apparent duplicate was caused by the OSO page rendering a second server-side applicant status form list above the searchable/paginated submissions table.
- Removed that redundant list. TOSA applicants now appear once in the searchable table, while the existing View Files, Download All, Return, Reject, and Approve actions remain available in the same row.
- Added a regression assertion that the page keeps the table renderer and no longer emits the duplicate `subsection` form controls.
- Verification: **46 tests, 266 assertions** passed, PHP syntax passed, Blade view cache passed, and `git diff --check` passed.

## Follow-up: paginate long report cards

- Added real client-side pagination for the Budget Utilization Expense Details table, Supporting Documents list, and Transaction History timeline. The consolidated budget data no longer truncates those lists before pagination can display the remaining records.
- Added the same page controls to the Financial Report ledger, supporting-document cards, and report-history timeline, and to the Accomplishment Report activity register and per-folder evidence documents.
- Pagination resets when the selected report period, activity, or search/filter changes; controls stay hidden for lists that fit on one page and show compact previous/page/next controls for longer data sets.
- Fixed the TOSA applicant, queue, and audit-log next-page buttons so their generated markup closes the class attribute correctly and the controls are actually clickable.
- Added regression assertions for the report pagination surfaces and TOSA pagination controls.

## Follow-up: remove redundant in-campus class schedule requirement

- Removed the `Class Schedule / Participant Schedule` row from the in-campus activity requirement set.
- It is no longer displayed in the activity-create checklist and is no longer included in the required-upload validation for new in-campus submissions.
- Existing stored submission files are not deleted; the change only removes the redundant requirement from the active workflow.
- Added a regression assertion that the in-campus form keeps the programme and meeting-minutes requirements while omitting class schedule.

## Follow-up: fix Student Voice verification queue layout

- Added a dedicated responsive table layout for the Student Voice verification queue.
- Long or unbroken report text now wraps inside the Report column instead of pushing into Submitted, Status, or Action.
- Fixed column sizing keeps dates, status badges, and View details actions aligned; narrow screens can scroll the table horizontally without overlap.
- Verification: Chrome confirmed the live queue renders without horizontal overflow or column collisions; Blade cache passed, `DashboardReportingTest` passed with **11 tests / 149 assertions**, and `git diff --check` passed.

## Follow-up: server-rendered SO and OSO report printing

- Replaced the Budget Utilization and Accomplishment Report `window.print()` actions with dedicated server-rendered print routes.
- The new report documents use the selected organization, department, semester, academic year, SDG, core-value, and participant filters, and render activity totals, expense/receipt rows, evidence files, audit references, and signature sections from the current server-side records.
- The standalone print pages intentionally exclude the office dashboard shell, sidebar, charts, client-side pagination, and interactive controls. They keep only a print toolbar and the report document, with an explicit `Print / Save as PDF` action.
- Verification: SO and OSO feature coverage passed (**2 tests / 24 assertions**), Blade cache and controller syntax passed, and Chrome confirmed both standalone pages have no sidebar and render the report tables.
