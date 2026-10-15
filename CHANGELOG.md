# Release Notes

## [Unreleased](https://github.com/laravel/laravel/compare/v13.7.0...13.x)

- Added public Open Graph sharing metadata and the existing OrgChain logo for Messenger/Facebook link previews. Crawler requests and the image return HTTP 200; previously cached 403 previews require a separate Meta rescrape rather than weakening private-document access or changing crawler security.
- Removed the temporary deployment-only network allowlist that caused 403 responses outside the operator's connection. Public pilot pages are reachable from other networks; authentication, role/ownership checks, private-storage denial and no-store response headers remain enforced.
- Fixed the student email-login modal initialization race: Send Verification Code now opens the code-entry step automatically after navigation and focuses the visible input, rather than requiring another Login click. The full SR Code format and six-digit email verification, expiry, attempt limits and anti-enumeration behavior are unchanged; production and fallback frontend bundles are refreshed.
- Added Hostinger KVM 2 pilot service configurations for a private Nginx/PHP-FPM origin, bounded MySQL/PHP workers, secure native PHP session cookies, persistent swap, serialized isolated conversion, log rotation and a restricted Cloudflare tunnel service. The Besu override pins the image, runs non-root validators with resource limits and publishes loopback-only RPC, not validator P2P. After the owner-confirmed CDN purge, the public hostname moved to the VPS-only connector and the laptop service was stopped/disabled. A fail-closed Nginx access include limits this initial HTTPS login check to the operator's current connections; unrestricted pilot release still awaits provider and real-login checks. Application records, APP_KEY and available original files are preserved.
- Replaced deterministic network generation with distinct cryptographically random secp256k1 validator/signer keys, production Composer ECC dependencies, private file creation and an existing-network/environment overwrite refusal. The owner-approved VPS replacement uses a new chain ID and archives the thesis chain without resetting application records. Vote proofs from a different contract are explicitly Historical, retain their original transaction and do not claim verification on the new chain; the admin view distinguishes that state from Failed and derives node counts from actual results. The owner approved retaining 32 pre-existing missing-original references with honest unavailable-file behavior.
- Restyled SO Budget Utilization with four summary cards, visible charts, a shared receipt drop zone and stacked multi-item expense cards. OSO keeps the real beginning-cash/inflow/outflow/ending-cash/approved-budget directory with live search and five-row paging; organization/activity expense drill-downs and ledger rules are unchanged.
- AR/FR period selectors refresh automatically without View period buttons. Report directory statuses omit redundant submission text and retain available-report links when either AR or FR is submitted. SO Accomplishment Report has a compact real-data desk and a completed DOC/DOCX/PDF upload dialog; original files are staged privately before separate submission, which immediately locks that report. Uploaded AR files do not depend on unrelated native draft evidence; native generated packets retain their completeness checks.
- Financial Report places Current Reports and searchable year/semester history first, with functional empty-state creation controls. OSO Archive now has searchable, scoped, academic-year-filtered organization cards, pagination and grouped preserved reports, approved renewals, activity documents and other files, including nested archives beyond the recent-file limit. Document groups identify their source activity/folder or renewal period; SO ownership and private local-disk file boundaries remain enforced.
- Shared Word/PDF previews use one responsive document shell with Close, Open in Tab and Download original. Word headers/footers and notes render through the existing DOCX viewer; legacy DOC converts privately using the isolated converter. Native PDF controls remain browser-dependent and downloads retain original bytes. Unsupported legacy Word financial files do not falsely unlock the SO's exact-file submission gate.
- Removed the student TOSA score/calculation section, its exclusive styles and unused portal evaluation/ranking queries. Application status, reference previews/downloads, document selection, submission and evaluator scoring/workbook endpoints remain unchanged.
- Student TOSA references now show only the title and consistent View/Download controls. Front Page and Submission Guide DOC files can be viewed through private, source-derived PDF previews using the existing isolated converter; original DOC downloads remain unchanged and temporary conversion workspaces are released after streaming. Removed filename/format/source metadata and confusing saved-file instructions. Whole-packet submission and locks are unchanged.
- Removed the extra submission explanation from each student TOSA file-selection card; the selected status, clickable filename and Cancel selection control remain.
- Restyled student TOSA into compact native collapsible sections with a single maroon palette, icon/chevron summaries, two-column preparation reference cards and responsive mobile stacking. Revision reveals reviewer remarks and editable credentials; file validation opens a collapsed credential section before reporting errors. Existing previews, original downloads, local file-selection feedback, whole-packet submission and post-submit locks are preserved.
- Student TOSA file selections now hide the conflicting Not uploaded/File unavailable block and show Selected · Not submitted instead. Cancelling or discarding a selection restores the prior state; revision replacements retain access to the saved original. Whole-packet saving, validation and submission locks are unchanged.
- Added colored calendar, approval-check and receipt icons to the OSO budget popup's Period, Total approved and Total encoded expenses cards. Decorative icons remain hidden from assistive technology, retain the original values, and fit desktop/mobile in both organization-list and activity-detail states.
- OSO organization activity cards now drill into activity expense details inside the same popup, with Back to activities/focus restoration, scope/payment tags, four actual-data budget figures, category/utilization and same-scale approved-versus-actual charts, and searchable expense rows with payment methods and original receipt previews. The shared private viewer supports multiple attachments and unchanged downloads over the active detail panel. Actual spending preserves and explains earlier recorded amounts without creating receipt rows; zero-budget, empty, overspend and missing-original states remain explicit. Organization/period/department boundaries and the SO budget surface are unchanged.
- Restyled Budget Utilization organization/activity information as equal-width desktop cards with pink/purple icon circles and badges, compact labeled values, field icons and vertical column dividers. Cards and metadata stack on smaller screens, and long organization/college names wrap without clipping. Existing live organization, activity and period hooks are preserved on SO and OSO; budget calculations and receipt workflows are unchanged.
- OSO Budget Utilization organization View Details now opens a centered, responsive organization-activities dialog with period, approved-budget and encoded-expense totals, numbered activity cards, expense counts and approved/spent amounts. Cards use the existing activity expense/receipt flow with organization, year, semester and department scope preserved. Encoded totals exclude legacy-only spending and rejected/unposted items. Added empty states, scrollable short-screen content, keyboard focus restoration and Close/×/Escape/backdrop dismissal; cash and approval writes are unchanged.
- Removed the explanatory sentence below the approved-activity dropdown. Actual approved-plan errors remain visible and linked to the field; required selection and submission rules are unchanged.
- Fixed the short activity-creation document preview: the dialog now has a definite viewport-relative height, its flex container fills it, and PDF frames fill the remaining document pane. Removed conflicting fixed iframe heights and minimums so desktop, narrow mobile and landscape previews keep Close/Download visible without clipping.
- Converted the genuine WPCF Word 97–2003 template to modern editable DOCX and switched activity-creation downloads, the in-campus template pack and Updates to it. The WPCF catalogue reads the packaged storage source directly instead of relying on the ignored local In Campus junction. Refreshed the source-derived PDF preview from the new DOCX to preserve the university logo, form lines and checkboxes without runtime conversion. Existing submitted documents and their review history remain unchanged; the original DOC is retained locally for provenance, not as an active template selector.
- Office Recent Updates now contains the four latest posted announcements only, matching View All → Updates instead of mixing in activity-review movements. SDO/OVCAA/OC proposal navigation uses the same Proposals label/icon as OSO, with matching queue headings and back links.
- SDO/OVCAA/OC can preview genuine submitted legacy DOC files through the existing private isolated converter, alongside DOCX/PDF previews, without weakening original document ownership or workflow-stage access. Close stays adjacent to Download original on desktop/mobile. Document badges reflect the latest passed OSO/SDO/OVCAA/OC stage, with return/rejection precedence and no display-side approval writes. Moved the SDO review checklist below Submitted Compliance Documents while retaining its required acknowledgement on the original endorsement form.
- AR/FR submission locks now require an accessible report-specific confirmation showing the selected academic year/semester and all-organizations scope. Cancel, Escape and backdrop dismissal do not POST; explicit confirmation submits the original scoped form once. Reopen remains direct and each report type/period stays independent. The dialog uses a blurred backdrop and avoids inherited page entrance animations so its content is immediately visible.
- TOSA now computes an evidence-backed score out of 100 from evaluator-entered GWA, academic awards, leadership and social responsibility, with exact milli-point arithmetic and criterion caps. Provisional/finalized evaluations are persisted separately from qualification decisions; finalization requires verified eligibility/GWA, a valid academic year and all seven real credentials. The versioned rubric corrects the bundled workbook's erroneous local-leadership references/rates and seminar-day arithmetic without altering original files or counting demonstration entries.
- TOSA Top10 now uses finalized, verified total points for accepted eligible candidates within an academic year, replacing the GWA-only list. Ties share competition ranks; ties crossing the tenth-place cutoff remain visible for committee resolution. Grade/verification changes and Return/Reject invalidate finalization; changed student evidence clears the old evaluation. Concurrent qualification/scoring writes recheck the current packet under a database row lock.
- Students can privately view their own score breakdown, evidence and rank, preview the original computation XLSX and preview/download a separate seven-sheet personal calculation workbook. Original downloads remain byte-for-byte unchanged; generated files contain actual saved inputs, caps and points without example achievements or evaluator identity. Read-only worksheet previews do not execute formulas, and exports neutralize formula-like input as literal strings. Deploy the additive `2026_10_11_000001_add_score_evaluation_to_tosa_applicants.php` migration; existing submissions are not reset.
- Activity proposals now choose an actual activity from the assigned organization's current approved Attachment I. Server-derived titles/references and source/version-bound keys replace free-text entry; blank templates, scans without text and stale/foreign selections cannot supply activities. ₱0 budgets are valid through final OC approval without requiring or reserving cash. Selected and saved filenames use the shared actual-document viewer; SO/OSO legacy DOC previews run LibreOffice in a private, network-isolated, resource-limited Docker container while retaining exact original downloads. Approved-plan previews use the genuine dossier source, never a blank-template fallback, and place Close beside Download original.
- Student TOSA saves the entire A–G packet with one Submit for Review and immediately locks it for OSO screening. **Revision** is the only submitted-state exception: the existing `returned` review stage is labeled Revision in both desks, exposes reviewer remarks and restores file-selection/Resubmit for Review controls. Corrected packets retain unchanged originals, return to Screening and lock again; changed evidence clears stale GWA verification and remarks. A shared model predicate keeps UI and server locks consistent; accepted records and stale revision pages cannot bypass the current persisted stage. Failed revisions preserve the previous packet and remain editable for correction. Per-file Save/Delete endpoints remain removed, with View/Download available throughout. Existing private originals, metadata, reference/template separation and centered PDF/DOCX previews are preserved; no applicant data reset is performed.
- Corrected the TOSA reference/submission distinction after reading the actual nomination DOCX and Word submission guide. The five supplied files remain official native-format reference/template downloads, not five mandatory uploads. The guide’s “Nomination Application Form” is the same TOSA Application Form 2025.docx, not an additional form; the upload row is now labeled “Completed TOSA Application Form” with explicit download/fill/sign/upload instructions. Applicants submit the guide’s A–G credential sections: cover/table of contents, completed application form, profile/résumé/CV, good moral, academic evidence, leadership evidence and school/non-school social-responsibility evidence. Supporting uploads accept PDF; the completed application form accepts PDF or DOCX. Source guides and the computation workbook no longer gate submission. References are separate from private evidence, the student reference library is collapsed by default, and OSO reference replacements are shared with students without altering bundled originals. Existing private metadata/files remain untouched; references do not automatically parse content/signatures or extract GWA, and no stale deadline is enforced.
- TOSA now opens on a compact Applicants desk with only Applicants, Requirements and Top10 tabs. Removed duplicate Overview/Review Queue panels, fabricated TOSA activity/demographic data, no-op requirement controls and missing-document counting. Search, real-data filters, pagination, filtered roster export, centered dossiers/private previews, persisted qualification/GWA/remarks and OSO reference management remain. Submission requires all guide-derived packet sections as readable, nonempty owner-bound private files; drafts and never-submitted rows are excluded from office lists/counts/exports and direct document/review access. Submitted historical rows remain visible after file loss, with honest file-open errors.
- TOSA template previews/downloads now preserve the storage-backed streamed response type, fixing an HTTP 500 caused by an incompatible controller return declaration. Original filenames and bytes remain unchanged.
- TOSA no longer requires a PIN or timed unlock. Active OSO accounts automatically receive full TOSA access through their office role, including legacy accounts saved with “No Access”; OVCAA retains read-only/evaluator authorization and private-document checks. Removed PIN settings, gateway, lock/timer UI and obsolete endpoints.
- Security: removed student OTP response/session/cache exposure, added non-enumerating code-request responses and per-account/per-IP authentication throttles. Browser OTP smoke checks now use mailbox-delivered codes.
- Security: SO desks require active organization assignments; receipt ZIP exports bind to the authenticated organization and reject foreign activities. Announcements and reminders require OSO authorization; TOSA document reads preserve office-role and reviewer-clearance requirements.
- Security: confidential uploads and controller downloads now use private storage. Added `documents:privatize` with hash-verified copies, safe resumability, collision/path checks, private diagnostics, and an explicit missing-original mode that preserves unresolved metadata and returns failure.
- Security: analytics CSV cells neutralize spreadsheet formulas; document, template-pack and receipt-export temporary files no longer use shared or orphaned paths.
- Security: blockchain and SMTP transports verify certificate chains/hostnames; private node read APIs require node authentication. Added production response headers, non-cacheable session pages, explicit trusted proxies, and secure production cookie defaults.
- Dependencies: updated vulnerable PHP/JavaScript packages and replaced the obsolete Ethereum transaction signer with maintained `kornrunner/ethereum-offline-raw-tx`; canonical EIP-155 signing compatibility is covered.
- Deployment: migrated 63 available confidential originals and retired 59 public copies locally; 32 pre-existing missing references remain preserved. Cloudflare cached copies and historical credential/data exposure require separate purge, provider rotation, and coordinated history cleanup; local code fixes do not erase them. No commit or push was made as part of this remediation.

- SO desk branding now shows the assigned organization’s short name, falling back to its full name.
- SO renewal now shows organization-specific details, the official form, and a progress-tracked A–J checklist. Edit information / Save changes explicitly persists adviser/dean names only, preserves selected FileLists and preview URLs, and keeps names-only drafts out of OSO review. Files remain local until the single complete Submit for Review; failed submissions preserve existing documents. Checklist colors/checks and readiness update for valid selections or physically present saved files. Submitted packets lock immediately in UI and server; OSO return or rejection permits eligible, in-window correction/resubmission, while approved packets remain final. PDF/image/DOCX and private SO/OSO native-DOC previews preserve original downloads. PHP's `max_file_uploads` must cover the checklist size (up to 30 OSO-managed requirements).
- SO renewal keeps the submission bar visible while scrolling, improves disabled-button contrast, and preserves space for the final attachment on desktop and mobile.
- OSO renewal packets have a submitted-documents review screen with per-attachment verification, revision/rejection remarks, and an all-documents-verified approval gate. Names-only drafts and never-submitted packets are excluded from applications and the submitted-documents screen. Fileless documents cannot be viewed, verified or approved. SO replacements reset document review; returned/rejected packets can be corrected and resubmitted. Approval remains final and does not activate organization records.
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
- Office settings show only profile and password controls for SO and other non-OSO desks, without redundant single-tab navigation or Save All. Unused personal session timers, notification/digest controls, language/theme/time-format preferences and display settings are removed; the English interface remains. Institutional settings updates are OSO-only. OSO retains account management, CSV exports, redacted configuration snapshots with reproducible SHA-256 checksums, and role-based TOSA permissions. Unused evaluation-mode, PIN gateway, archive/retention and cloud-sync controls, and fabricated backup/audit metadata are removed. Inactive saved keys are excluded from the active settings schema; profile and password saves remain separate.
- OSO Budget Utilization now includes a read-only Organization Financial Overview with consolidated beginning cash, inflow, outflow and ending cash, plus independent final-approved activity budgets and per-organization rows. Registered organizations without an account show zero cash; historical organizations and canonical annual accounts are retained without duplicate-account or BudgetItem mirror counting. View Details shows selected-period income, posted receipt and preserved legacy-spending entries with pagination, alongside existing activity utilization. Organization, department, academic-year and semester filters apply to rows, totals and print/export; semester openings include earlier postings without carrying balances between years. The overview and SO Financial Report share batched ledger statement calculations, with no new cash store, writes, workbook credits or reservation outflows. SO remains restricted to its assigned organization and keeps expense entry; desktop/mobile overview and filtered print rendering are verified.
- SO and OSO Budget Utilization receipt View/status links now open a centered, responsive preview card without leaving the expense table. Multiple attachments can be selected or stepped through; JPG/PNG images, PDFs and legacy DOCX receipts render in the card. Download Original preserves the selected file's original bytes and filename, while Open in Tab remains explicit. Missing or inaccessible files show an error rather than an empty preview. Authenticated receipt access, cash totals and stored documents are unchanged.
- OSO Budget Utilization now follows Organizations → View Details → approved activities → View Expenses. Literal organization/college and activity searches include result counts, Search/Clear actions and empty states without changing cash totals. Activity lists and drill-downs preserve organization, department, academic year and semester filters; unavailable, unapproved, cross-organization and wrong-period activity requests never fall back to consolidated expenses. Activity information and summary cards precede encoded expenses, with charts collapsed below them and organization cash history optional below the activity list. Mobile organization cards keep View Details visible, and back links and print/export retain their scope. SO expense entry, receipt previews and cash calculations are unchanged.
- OSO can independently lock or reopen AR and FR submissions for an academic year and semester from Submitted Semester Reports. Controls apply to all organizations in that period; existing and unconfigured periods remain open until OSO locks them. SO sees the locked status and disabled submission controls but can still prepare, preview and download files. Every submission endpoint checks the persisted filing gate under the same database row lock used by OSO updates, including returned-report resubmissions and stale open pages. Locking does not change submitted reports, review decisions, files or cash, and reopening retains AR completeness and exact-file FR preview requirements.
- OSO report navigation now combines AR and FR under AR & FR Reports while retaining independent review tabs, filing locks and SO report pages. The organization directory shows five entries per page, searches all organizations before pagination and preserves search/page state when viewing reports or switching tabs. View Submitted Reports is disabled when neither report has a viewable submitted file; available links open the requested report type or the other submitted type instead of an empty tab.
- OSO settings save real General/profile/password values with accurate validation and partial Save All feedback; account updates refresh visible identity without losing other drafts. Officer creation normalizes login-compatible institutional emails, constrains roles/TOSA clearance and respects username limits; deactivation blocks access and cannot target the current account. PNG/JPEG logo upload/reset updates the preview only after server success. Role-based read-only, evaluator and OSO template-management boundaries are enforced, including manifest exports and template upload paths. Downloads preserve actual CSV records and report failures; desktop/mobile settings support searchable navigation and keyboard-safe dialogs. Existing account clearances are not rewritten.
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
