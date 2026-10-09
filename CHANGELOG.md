# Release Notes

## [Unreleased](https://github.com/laravel/laravel/compare/v13.7.0...13.x)

- SO desk branding now shows the assigned organization’s short name, falling back to its full name.
- SO renewal now shows organization-specific details, the official form, and a progress-tracked A–J checklist. Details and picked files stay local until Submit for Review; there is no draft save or automatic upload. Complete packets save together, and failed submissions leave saved details and documents unchanged. Missing, empty or directory-only file records never count as uploaded or verified. Selected filenames replace “No file selected” and open local or authenticated saved files without a separate uploaded-file View button or repeated submission-status wording. The master form, official attachment View links and selected filenames share a centered, responsive preview card for PDF, JPG/PNG and DOCX files. Original downloads remain available; opening a new tab is an explicit action. Edit Information stays visible while editing; finalized packets remain locked, with saved-file previews still available. PHP's `max_file_uploads` must cover the checklist size (up to 30 OSO-managed requirements).
- SO renewal keeps the submission bar visible while scrolling, improves disabled-button contrast, and preserves space for the final attachment on desktop and mobile.
- OSO renewal packets now have a submitted-documents review screen with per-attachment verification, revision/rejection remarks, and an all-documents-verified approval gate. Legacy drafts and never-submitted packets are excluded from applications and cannot open the submitted-documents screen. Fileless documents have no View or review actions and cannot be verified or approved. Document rows show the file type and upload date without filenames; filenames remain available inside View. SO replacements reset review status; final packet decisions are locked without activating organization records.
- SO activity creation now uses numbered information/requirement cards, in-campus/off-campus choices, colored SDG tiles, real renewal-status badges, and progress-tracked uploads. SDG 9 uses stacked cubes, and SDG 16 uses a dove, olive branch, and gavel; checkbox values remain unchanged. All activity templates use professional names in checklists, previews, individual downloads, and ZIP packs without numbered copy suffixes; original source documents and organization uploads remain unchanged. Template and submitted-document preview cards stay centered on desktop and mobile, with original Word page dimensions preserved and large pages scrollable. The official checklists contain only the 10 supplied in-campus documents and 9 supplied off-campus documents; submission checks and template packs use those same lists. Additional, conditional, and bulk supporting-file upload slots are removed. Draft saving is removed: only an explicit Submit for Review can save a complete packet. Missing, empty or directory-only file records cannot satisfy required uploads. Returned packets can be corrected and resubmitted; unrelated historical uploads are retained separately. The submission bar remains visible on desktop and mobile.
- OSO renewal now has dashboard totals, scheduled filing-window controls, searchable/status-filtered applications with pagination and CSV export, and official-template cards with Add/Edit/Replace/Remove dialogs. Template changes preserve signed uploads; previews follow the current file instead of stale paired PDFs. Attachment A's current template mapping is restored to the original Commitment Letter of the Adviser Word document and matching PDF preview. Requirement codes remain stable, and new filing periods retain historical windows without inheriting old approvals.
- SO Financial Report now uses the supplied seven-sheet Excel workbook, with ledger-backed receipt/disbursement totals, per-activity cash-flow charts, academic-year/semester filters, saved opening balances, current reports and submission-date-based history. Completed `.xlsx` reports are validated before creation; centered worksheet previews unlock FR submission only for the exact viewed file version, with ownership, status and real-file checks. Original downloads and source templates stay unchanged; exports carry actual financial data across the official forms without example transactions or executable imported text. Desktop, mobile and short-screen layouts are supported.
- SO Budget Utilization no longer shows the annual Recorded fund balance card or Set organization funds form. Obsolete annual fund-setting endpoints and forms are removed. Approved activity budgets, expense recording, remaining-budget calculations and print/export continue; a Financial Report link opens the selected academic year's shared cash management.
- SO Financial Report, activity approvals, Budget Utilization and dashboard cash totals now share one organization cash ledger. Assigned SO accounts save annual opening cash and record actual income with unique references and retry-safe request keys. Approved allocations reserve unspent cash; posted receipts reduce cash once, including pending seals, without a second deduction after verification or retry. Opening cash cannot fall below committed spending and reservations. Existing fund capital is preserved as the initial saved opening, existing receipt rows remain authoritative, and earlier unreceipted spending is retained without counting receipts twice. Legacy account fields and sources remain unchanged as audit data. Semester openings include earlier postings; annual accounts remain independent. Excel uploads are reporting documents, never additional credits or debits; exports use saved cash rather than an unsaved projection.
- SO Financial Report now has one Cash Flow overview instead of duplicate organization-cash totals. Opening/inflow actions sit in its header; a compact annual funding strip shows only Reserved and Available to allocate. Semester-filtered cash totals remain separate from year-wide funding, explanations are condensed, and desktop/mobile controls retain the existing saved-ledger behavior.
- SO dashboard cards now have consistent section/card gutters. Budget Snapshot removes duplicate out-of-grid tiles and stray closing tags, keeps peso values beneath their icons without crowding, and adapts to four desktop, two laptop/tablet and one mobile column. Existing dashboard figures and other office layouts remain unchanged.
- Office calendars now use permitted, persisted activity identities without demo records or title-based deduplication. Approval badges follow the real workflow separately from upcoming/ongoing/past timing; multi-day schedules include every occupied day with midnight-exclusive endings and complete date/time ranges. Today, Month/Agenda views, mobile-default agenda, literal activity/venue search, scope/approval/schedule filters and centered exact-activity details preserve navigation state. Calendars remain read-only; activity dates and approval workflows are unchanged.
- SO Accomplishment Report now builds activity narratives and semester packets from the supplied official Word templates and accomplishment-report reference. Approved, ended activities use explicitly selected reporting periods, actual male/female attendance, objectives, outcomes, problems, recommendations and captioned JPEG/PNG evidence. File selection stays local until Save report & stage Word; generated packets are staged separately from final AR submission. Activity-linked collections and posted receipt expenses are captured by activity ID with exact peso totals, real receipt references and retained supporting scans; saving, previewing and exporting never change the cash ledger. Genuine Word downloads and centered desktop/mobile previews preserve long-bond portrait/landscape pages, with Print / Save as PDF. Complete native evidence is required before AR submission; OSO reads submitted snapshots, and submitted or finalized AR reports cannot be edited. Original templates, receipt files and historical manual report uploads remain intact.
- Budget Utilization removes the Receipt History, Receipt Seal Details and Transaction History cards, including their page-only styles and rendering. Receipt entry, expense itemization/search/pagination, activity and period filters, budget totals, receipt viewing and print/export remain available; stored receipts, verification processing and audit records are unchanged.
- SO Financial Report removes the Annual funding / Reserved / Available to allocate strip and its page-only styles. The overview retains beginning balance, incoming cash, outgoing cash and ending balance, with opening-balance and inflow actions unchanged. Approved-budget reservations remain in the shared ledger and continue to limit budget approvals without being counted as cash outflow.
- AR and FR now submit independently from their SO tabs. OSO browses registered organizations by academic year and semester, opens View Submitted Reports, and switches between AR and FR tabs with separate submission/open/review dates, decisions and remarks. Returning one report unlocks only that type and preserves revision remarks until resubmission; rejection requires a reason and is final. Verification archives only the selected report’s latest readable file. Draft and returned revisions are unavailable to OSO, while SO retains its own previews. Native AR completion and exact-file FR workbook preview gates remain independent; same-type duplicate locked statuses cannot be bypassed. Existing report files, historical paired archives and the shared cash ledger are preserved. The combined upload/submission panel is removed; desktop/mobile report previews stay centered.
- Office settings show only profile and password controls for SO and other non-OSO desks, without redundant single-tab navigation or Save All. Unused personal session timers, notification/digest controls, language/theme/time-format preferences and display settings are removed; the English interface remains. Institutional settings updates are OSO-only. OSO retains account management, CSV exports, redacted configuration snapshots with reproducible SHA-256 checksums, and TOSA PIN controls. Unused evaluation-mode, archive/retention and cloud-sync controls, fabricated backup/audit metadata, and the unsupported Master PIN are removed. Inactive saved keys are excluded from the active settings schema; profile, password and TOSA PIN saves remain separate.
- OSO Budget Utilization now includes a read-only Organization Financial Overview with consolidated beginning cash, inflow, outflow and ending cash, plus independent final-approved activity budgets and per-organization rows. Registered organizations without an account show zero cash; historical organizations and canonical annual accounts are retained without duplicate-account or BudgetItem mirror counting. View Details shows selected-period income, posted receipt and preserved legacy-spending entries with pagination, alongside existing activity utilization. Organization, department, academic-year and semester filters apply to rows, totals and print/export; semester openings include earlier postings without carrying balances between years. The overview and SO Financial Report share batched ledger statement calculations, with no new cash store, writes, workbook credits or reservation outflows. SO remains restricted to its assigned organization and keeps expense entry; desktop/mobile overview and filtered print rendering are verified.
- SO and OSO Budget Utilization receipt View/status links now open a centered, responsive preview card without leaving the expense table. Multiple attachments can be selected or stepped through; JPG/PNG images, PDFs and legacy DOCX receipts render in the card. Download Original preserves the selected file's original bytes and filename, while Open in Tab remains explicit. Missing or inaccessible files show an error rather than an empty preview. Authenticated receipt access, cash totals and stored documents are unchanged.
- OSO Budget Utilization now follows Organizations → View Details → approved activities → View Expenses. Literal organization/college and activity searches include result counts, Search/Clear actions and empty states without changing cash totals. Activity lists and drill-downs preserve organization, department, academic year and semester filters; unavailable, unapproved, cross-organization and wrong-period activity requests never fall back to consolidated expenses. Activity information and summary cards precede encoded expenses, with charts collapsed below them and organization cash history optional below the activity list. Mobile organization cards keep View Details visible, and back links and print/export retain their scope. SO expense entry, receipt previews and cash calculations are unchanged.
- OSO can independently lock or reopen AR and FR submissions for an academic year and semester from Submitted Semester Reports. Controls apply to all organizations in that period; existing and unconfigured periods remain open until OSO locks them. SO sees the locked status and disabled submission controls but can still prepare, preview and download files. Every submission endpoint checks the persisted filing gate under the same database row lock used by OSO updates, including returned-report resubmissions and stale open pages. Locking does not change submitted reports, review decisions, files or cash, and reopening retains AR completeness and exact-file FR preview requirements.
- OSO report navigation now combines AR and FR under AR & FR Reports while retaining independent review tabs, filing locks and SO report pages. The organization directory shows five entries per page, searches all organizations before pagination and preserves search/page state when viewing reports or switching tabs. View Submitted Reports is disabled when neither report has a viewable submitted file; available links open the requested report type or the other submitted type instead of an empty tab.
- OSO settings save real General/profile/security values with accurate validation and partial Save All feedback; account updates refresh visible identity without losing other drafts. Officer creation normalizes login-compatible institutional emails, constrains roles/TOSA clearance and respects username limits; deactivation blocks access and cannot target the current account. PNG/JPEG logo upload/reset updates the preview only after server success. TOSA now uses a user/config-bound server PIN unlock with fixed expiry and explicit re-lock, without default PINs, browser-storage bypasses or locked applicant data. No Access, read-only, evaluator and OSO template-management boundaries are enforced, including manifest exports and template upload paths. Downloads preserve actual CSV records and report failures; desktop/mobile settings support searchable navigation and keyboard-safe dialogs. Existing account clearances and PINs are not rewritten.
- SO now has an organization-scoped Archive with nested folders, explicit DOCX uploads, centered previews of actual Word content and original-file downloads. New manual uploads use private storage; folder ancestry, pickers, recents, metadata and file endpoints enforce the assigned organization, including forged requests. OSO retains cross-organization access and legacy report files keep their original storage. Archive counts and activity-file links use real records instead of demo documents, dummy sizes or empty previews. Manual archiving does not submit or alter AR/FR reports.
- OSO Users & Roles now manages organization-linked SO accounts alongside office accounts, with account search, profile editing, temporary-password reset and atomic SO officer turnover. Role and organization cannot be changed through profile edits; turnover creates a separate incoming identity, disables the outgoing account and retains organization records and authorship. New/reset accounts must change their temporary password before any other desk data or action is accessible. Per-account credential versions and remembered-token rotation revoke stale sessions after password/email/status changes, including system-admin status updates; valid self-service changes preserve the current session. Concurrent/repeated turnovers create at most one replacement, passwords are excluded from flashed form input, and existing accounts remain grandfathered. Account dialogs and first-login forms support keyboard and mobile use.
- Source-control hygiene excludes local environment variants, private keys, database dumps, real-student TOSA seed records and populated report examples while preserving their local copies. Account seeders and office smoke scripts no longer publish default passwords; student demo identities are synthetic. Blockchain node secrets are environment-only, missing configuration fails closed, and launcher output/connection handouts omit the secret value. Applicant review metadata and document paths resolve the selected student instead of a hardcoded personal dossier. Existing account credentials, database records and private environment values are unchanged; removing files from the latest tree does not remove prior Git-history exposure.

## [v13.7.0](https://github.com/laravel/laravel/compare/v13.6.0...v13.7.0) - 2026-05-14

**Full Changelog**: https://github.com/laravel/laravel/compare/v13.6.0...v13.7.0

## [v13.6.0](https://github.com/laravel/laravel/compare/v13.5.0...v13.6.0) - 2026-05-11

* Remove Pdo/Mysql const workaround by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/laravel/pull/6810

## [v13.5.0](https://github.com/laravel/laravel/compare/v13.4.0...v13.5.0) - 2026-04-30

* Use the Vite font plugin for application fonts by [@WendellAdriel](https://github.com/WendellAdriel) in https://github.com/laravel/laravel/pull/6806

## [v13.4.0](https://github.com/laravel/laravel/compare/v13.3.0...v13.4.0) - 2026-04-28

* Add @no_additional_args to composer test script config clear by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/laravel/pull/6799
* [13.x] Adds pao by default by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/laravel/pull/6802

## [v13.3.0](https://github.com/laravel/laravel/compare/v13.2.0...v13.3.0) - 2026-04-16

* [13.x] enable npm audit by default by [@leo95batista](https://github.com/leo95batista) in https://github.com/laravel/laravel/pull/6788
* Update changelog link to Laravel framework repo by [@Rattone](https://github.com/Rattone) in https://github.com/laravel/laravel/pull/6790
* [13x] Add .codex to .gitignore by [@amdad121](https://github.com/amdad121) in https://github.com/laravel/laravel/pull/6793

## [v13.2.0](https://github.com/laravel/laravel/compare/v13.1.2...v13.2.0) - 2026-04-09

* Remove axios and enable ignore-scripts by [@WendellAdriel](https://github.com/WendellAdriel) in https://github.com/laravel/laravel/pull/6778
* Add /.cursor/ to .gitignore by [@workwithbinu](https://github.com/workwithbinu) in https://github.com/laravel/laravel/pull/6782
* Remove '.fleet' from .gitignore by [@dominiq007](https://github.com/dominiq007) in https://github.com/laravel/laravel/pull/6783
* Support all compose file naming conventions in editorconfig by [@mmachatschek](https://github.com/mmachatschek) in https://github.com/laravel/laravel/pull/6786

## [v13.1.2](https://github.com/laravel/laravel/compare/v13.1.1...v13.1.2) - 2026-03-31

* Prevents installed package from executing malicious code via `postinstall` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/laravel/pull/6777
* Add missing comma in axios by [@aziyan99](https://github.com/aziyan99) in https://github.com/laravel/laravel/pull/6779

## [v13.1.1](https://github.com/laravel/laravel/compare/v13.1.0...v13.1.1) - 2026-03-31

* Update .gitignore by [@Cegem-360](https://github.com/Cegem-360) in https://github.com/laravel/laravel/pull/6774
* [security] pin axios version by [@NickSdot](https://github.com/NickSdot) in https://github.com/laravel/laravel/pull/6776

## [v13.1.0](https://github.com/laravel/laravel/compare/v12.12.2...v13.1.0) - 2026-03-18

* Change back minimum-stability to stable by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/laravel/pull/6766
* Vite 8 support

## [v12.12.2](https://github.com/laravel/laravel/compare/v12.12.1...v12.12.2) - 2026-03-14

* [12.x] Add `APP_NAME` fallback in Slack log channel username by [@hamedelasma](https://github.com/hamedelasma) in https://github.com/laravel/laravel/pull/6762

## [v12.12.1](https://github.com/laravel/laravel/compare/v12.12.0...v12.12.1) - 2026-03-10

* [12.x] Makes imports consistent by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/laravel/pull/6760

## [v12.12.0](https://github.com/laravel/laravel/compare/v12.11.2...v12.12.0) - 2026-03-09

* Update phpunit version to ^11.5.50 to address CVE by [@PerryvanderMeer](https://github.com/PerryvanderMeer) in https://github.com/laravel/laravel/pull/6746
* [12.x] Add `APP_NAME` fallback in mail config by [@apoorvdarshan](https://github.com/apoorvdarshan) in https://github.com/laravel/laravel/pull/6755
* [12.x] Neutralize DB_URL in default phpunit.xml by [@Husseinadq](https://github.com/Husseinadq) in https://github.com/laravel/laravel/pull/6761

## [v12.11.2](https://github.com/laravel/laravel/compare/v12.11.1...v12.11.2) - 2026-01-19

* [12.x] Update composer dev script to ensure no timeout by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/laravel/pull/6735
* [12.x] Update jobs/cache migrations by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/laravel/pull/6736
* [12.x] Remove failed jobs indexes by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/laravel/pull/6739
* [12.x] Add `APP_URL` fallback in filesystems config by [@KentarouTakeda](https://github.com/KentarouTakeda) in https://github.com/laravel/laravel/pull/6742
* chore: Update outdated GitHub Actions version by [@pgoslatara](https://github.com/pgoslatara) in https://github.com/laravel/laravel/pull/6743

## [v12.11.1](https://github.com/laravel/laravel/compare/v12.11.0...v12.11.1) - 2025-12-23

* Use environment variable for `DB_SSLMODE` - Postgres by [@robsontenorio](https://github.com/robsontenorio) in https://github.com/laravel/laravel/pull/6727
* fix: ensure APP_URL does not have trailing slash in filesystem by [@msamgan](https://github.com/msamgan) in https://github.com/laravel/laravel/pull/6728

## [v12.11.0](https://github.com/laravel/laravel/compare/v12.10.1...v12.11.0) - 2025-11-25

* fix: cookies are not available for subdomains by default by [@joostdebruijn](https://github.com/joostdebruijn) in https://github.com/laravel/laravel/pull/6705
* Fix PHP 8.5 PDO Driver Specific Constant Deprecation by [@RyanSchaefer](https://github.com/RyanSchaefer) in https://github.com/laravel/laravel/pull/6710
* Ignore Laravel compiled views for Vite  by [@QistiAmal1212](https://github.com/QistiAmal1212) in https://github.com/laravel/laravel/pull/6714

## [v12.10.1](https://github.com/laravel/laravel/compare/v12.10.0...v12.10.1) - 2025-11-06

* Update schema URL in package.json by [@robinmiau](https://github.com/robinmiau) in https://github.com/laravel/laravel/pull/6701

## [v12.10.0](https://github.com/laravel/laravel/compare/v12.9.1...v12.10.0) - 2025-11-04

* Add background driver by [@barryvdh](https://github.com/barryvdh) in https://github.com/laravel/laravel/pull/6699

## [v12.9.1](https://github.com/laravel/laravel/compare/v12.9.0...v12.9.1) - 2025-10-23

* [12.x] Replace Bootcamp with Laravel Learn by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6692
* [12.x] Comment out CLI workers for fresh applications by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/laravel/pull/6693

## [v12.9.0](https://github.com/laravel/laravel/compare/v12.8.0...v12.9.0) - 2025-10-21

**Full Changelog**: https://github.com/laravel/laravel/compare/v12.8.0...v12.9.0

## [v12.8.0](https://github.com/laravel/laravel/compare/v12.7.1...v12.8.0) - 2025-10-20

* [12.x] Makes test suite using broadcast's `null` driver by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/laravel/pull/6691

## [v12.7.1](https://github.com/laravel/laravel/compare/v12.7.0...v12.7.1) - 2025-10-15

* Added `failover` driver to the `queue` config comment.  by [@sajjadhossainshohag](https://github.com/sajjadhossainshohag) in https://github.com/laravel/laravel/pull/6688

## [v12.7.0](https://github.com/laravel/laravel/compare/v12.6.0...v12.7.0) - 2025-10-14

**Full Changelog**: https://github.com/laravel/laravel/compare/v12.6.0...v12.7.0

## [v12.6.0](https://github.com/laravel/laravel/compare/v12.5.0...v12.6.0) - 2025-10-02

* Fix setup script by [@goldmont](https://github.com/goldmont) in https://github.com/laravel/laravel/pull/6682

## [v12.5.0](https://github.com/laravel/laravel/compare/v12.4.0...v12.5.0) - 2025-09-30

* [12.x] Fix type casting for environment variables in config files by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6670
* Fix CVEs affecting vite by [@faissaloux](https://github.com/faissaloux) in https://github.com/laravel/laravel/pull/6672
* Update .editorconfig to target compose.yaml by [@fredikaputra](https://github.com/fredikaputra) in https://github.com/laravel/laravel/pull/6679
* Add pre-package-uninstall script to composer.json by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/laravel/pull/6681

## [v12.4.0](https://github.com/laravel/laravel/compare/v12.3.1...v12.4.0) - 2025-08-29

* [12.x] Add default Redis retry configuration by [@mateusjatenee](https://github.com/mateusjatenee) in https://github.com/laravel/laravel/pull/6666

## [v12.3.1](https://github.com/laravel/laravel/compare/v12.3.0...v12.3.1) - 2025-08-21

* [12.x] Bump Pint version by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6653
* [12.x] Making sure all related processed are closed when terminating the currently command by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6654
* [12.x] Use application name from configuration by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6655
* Bring back postAutoloadDump script by [@jasonvarga](https://github.com/jasonvarga) in https://github.com/laravel/laravel/pull/6662

## [v12.3.0](https://github.com/laravel/laravel/compare/v12.2.0...v12.3.0) - 2025-08-03

* Fix Critical Security Vulnerability in form-data Dependency by [@izzygld](https://github.com/izzygld) in https://github.com/laravel/laravel/pull/6645
* Revert "fix" by [@RobertBoes](https://github.com/RobertBoes) in https://github.com/laravel/laravel/pull/6646
* Change composer post-autoload-dump script to Artisan command by [@lmjhs](https://github.com/lmjhs) in https://github.com/laravel/laravel/pull/6647

## [v12.2.0](https://github.com/laravel/laravel/compare/v12.1.0...v12.2.0) - 2025-07-11

* Add Vite 7 support by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/laravel/pull/6639

## [v12.1.0](https://github.com/laravel/laravel/compare/v12.0.11...v12.1.0) - 2025-07-03

* [12.x] Disable nightwatch in testing by [@laserhybiz](https://github.com/laserhybiz) in https://github.com/laravel/laravel/pull/6632
* [12.x] Reorder environment variables in phpunit.xml for logical grouping by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6634
* Change to hyphenate prefixes and cookie names by [@u01jmg3](https://github.com/u01jmg3) in https://github.com/laravel/laravel/pull/6636
* [12.x] Fix type casting for environment variables in config files by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6637

## [v12.0.11](https://github.com/laravel/laravel/compare/v12.0.10...v12.0.11) - 2025-06-10

**Full Changelog**: https://github.com/laravel/laravel/compare/v12.0.10...v12.0.11

## [v12.0.10](https://github.com/laravel/laravel/compare/v12.0.9...v12.0.10) - 2025-06-09

* fix alphabetical order by [@Khuthaily](https://github.com/Khuthaily) in https://github.com/laravel/laravel/pull/6627
* [12.x] Reduce redundancy and keeps the .gitignore file cleaner by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6629
* [12.x] Fix: Add void return type to satisfy Rector analysis by [@Aluisio-Pires](https://github.com/Aluisio-Pires) in https://github.com/laravel/laravel/pull/6628

## [v12.0.9](https://github.com/laravel/laravel/compare/v12.0.8...v12.0.9) - 2025-05-26

* [12.x] Remove apc by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6611
* [12.x] Add JSON Schema to package.json by [@martinbean](https://github.com/martinbean) in https://github.com/laravel/laravel/pull/6613
* Minor language update by [@woganmay](https://github.com/woganmay) in https://github.com/laravel/laravel/pull/6615
* Enhance .gitignore to exclude common OS and log files by [@mohammadRezaei1380](https://github.com/mohammadRezaei1380) in https://github.com/laravel/laravel/pull/6619

## [v12.0.8](https://github.com/laravel/laravel/compare/v12.0.7...v12.0.8) - 2025-05-12

* [12.x] Clean up URL formatting in README by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6601

## [v12.0.7](https://github.com/laravel/laravel/compare/v12.0.6...v12.0.7) - 2025-04-15

* Add `composer run test` command by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/laravel/pull/6598
* Partner Directory Changes in ReadME by [@joshcirre](https://github.com/joshcirre) in https://github.com/laravel/laravel/pull/6599

## [v12.0.6](https://github.com/laravel/laravel/compare/v12.0.5...v12.0.6) - 2025-04-08

**Full Changelog**: https://github.com/laravel/laravel/compare/v12.0.5...v12.0.6

## [v12.0.5](https://github.com/laravel/laravel/compare/v12.0.4...v12.0.5) - 2025-04-02

* [12.x] Update `config/mail.php` to match the latest core configuration by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6594

## [v12.0.4](https://github.com/laravel/laravel/compare/v12.0.3...v12.0.4) - 2025-03-31

* Bump vite from 6.0.11 to 6.2.3 - Vulnerability patch by [@abdel-aouby](https://github.com/abdel-aouby) in https://github.com/laravel/laravel/pull/6586
* Bump vite from 6.2.3 to 6.2.4 by [@thinkverse](https://github.com/thinkverse) in https://github.com/laravel/laravel/pull/6590

## [v12.0.3](https://github.com/laravel/laravel/compare/v12.0.2...v12.0.3) - 2025-03-17

* Remove reverted change from CHANGELOG.md by [@AJenbo](https://github.com/AJenbo) in https://github.com/laravel/laravel/pull/6565
* Improves clarity in app.css file by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6569
* [12.x] Refactor: Structural improvement for clarity by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6574
* Bump axios from 1.7.9 to 1.8.2 - Vulnerability patch by [@abdel-aouby](https://github.com/abdel-aouby) in https://github.com/laravel/laravel/pull/6572
* [12.x] Remove Unnecessarily [@source](https://github.com/source) by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6584

## [v12.0.2](https://github.com/laravel/laravel/compare/v12.0.1...v12.0.2) - 2025-03-04

* Make the github test action run out of the box independent of the choice of testing framework by [@ndeblauw](https://github.com/ndeblauw) in https://github.com/laravel/laravel/pull/6555

## [v12.0.1](https://github.com/laravel/laravel/compare/v12.0.0...v12.0.1) - 2025-02-24

* [12.x] prefer stable stability by [@pataar](https://github.com/pataar) in https://github.com/laravel/laravel/pull/6548

## [v12.0.0 (2025-??-??)](https://github.com/laravel/laravel/compare/v11.0.2...v12.0.0)

Laravel 12 includes a variety of changes to the application skeleton. Please consult the diff to see what's new.
