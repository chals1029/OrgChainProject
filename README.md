# OrgChain

OrgChain is a blockchain-backed student organization management platform for Batangas State University, The National Engineering University (BatStateU The NEU).

It links Student Organizations (SO) with the Office of Student Organizations (OSO), the Sustainable Development Office (SDO), and OVCAA in one workflow: activity proposals move through verification and approval, budgets are utilized against approved activities with receipt evidence, and reports, announcements, and archives stay in a single system of record.

## Portals and routes

### Organization / office desk (`/office-desk`, `office.auth` middleware)

| Page | Route | What it does |
| --- | --- | --- |
| Dashboard | `GET /office-desk` | Activity stats, upcoming activity and workflow tracker; Recent Updates contains posted announcements only and View All opens Updates |
| Dashboard reporting | Dashboard filters | Calendar-year submission cohorts; distinct recognized organizations for A.Y. N–(N+1); recorded return events and evidence-backed elapsed review days |
| Analytics | `GET /office-desk/analytics` | Pipeline breakdown by status, top utilized budgets |
| Activities / Proposals | `GET /office-desk/activities[?activity=slug]` | SO activity management; OSO/SDO/OVCAA/OC proposal review with for-approval / approved / in-review / returned filters |
| Create activity | `GET /office-desk/activities/create[?edit=slug]` | Select an activity from the assigned organization’s approved Attachment I and prepare the in-campus/off-campus checklist |
| Submit activity | `POST /office-desk/activities`, `PUT /office-desk/activities/{submission}` | Derives title/reference from the selected approved-plan entry and submits structured details plus all required uploads together; ₱0 budgets are allowed |
| Calendar | `GET /office-desk/calendar[?month=YYYY-MM]` | Month-only grid with Today/Month controls, real day/event details, Enter search and Ongoing/Upcoming panels below |
| Budget Utilization | `GET /office-desk/budget-utilization` | Record expenses per approved activity, receipt attachment upload, verification queue |
| Submit receipt | `POST /office-desk/budget-utilization/receipt-reviews` | Stores reviewed receipt + expense for office review |
| Financial Report | `GET /office-desk/financial-report` | Semester expense lines sourced from recorded utilization |
| Accomplishment Report | `GET /office-desk/accomplishment-report` | End-of-term accomplishment submissions |
| Updates | `GET /office-desk/updates` | Read-only OSO announcements and downloadable template documents |
| Archive | `GET /office-desk/archive` | SO sees only its assigned organization’s folders, documents and activity files; OSO retains access across organizations |
| New folder / upload | `POST /office-desk/archive/folders`, `POST /office-desk/archive/documents` | Creates nested folders and explicitly saves uploads on the private `local` disk; DOCX content is validated and files are limited to 20 MB |
| Archive view / download | `GET /office-desk/archive/documents/{document}/view`, `GET /office-desk/archive/documents/{document}/download` | Authenticated, ownership-checked previews and original downloads; activity files use the equivalent `activity-documents` routes |
| TOSA | `GET /office-desk/tosa` | PIN-free, role-authorized review desk with Applicants, Requirements and Top10 |
| Renewal information | `POST /office-desk/renewal/information` | Saves the assigned organization’s adviser/dean names only; does not upload files or submit the packet |
| Native DOC preview | `POST /office-desk/documents/preview/convert` | Active SO/OSO/SDO/OVCAA/OC accounts can temporarily convert genuine Word 97–2003 bytes to a private PDF; original document access remains ownership/stage-checked |

OSO dashboard reporting: **Recognized Organizations** counts distinct organization names from valid active academic-year roster entries and approved renewal windows. Selecting calendar year N maps this roster KPI to **A.Y. N–(N+1)**; All Calendar Years deduplicates across recorded academic years. Semester/month/scope filter submissions, not academic-year recognition. No historical roster or approved renewal means zero, not today's active organization count. Updates and the dashboard registry context instead show the same **registered organizations across all academic years, including inactive records**; Renewal identifies registered/enabled registry totals separately from its period's submitted/approved packets.

**View Activity Pipeline** carries `submission_year`, `submission_semester`, `submission_month` and `activity_scope`; a college ranking link also carries `college`. College totals aggregate every visible organization in that college, not only its student council. Proposal filters intersect with organization and scheduled **Activity A.Y.**, and detail/back links retain context. Submission cohorts use proposal creation dates, independently of scheduled activity dates. SO query parameters cannot expand access beyond the assigned organization.

Dashboard and analytics activity pipelines use persisted, role-visible activities only. An organization with no activity records gets an empty pipeline and no fabricated activity dates, budgets, document names or pending counts; hardcoded demonstration activities are never substituted.

The OSO dashboard no longer displays **Recorded Return / Revision Analysis** or **Average Processing Time** charts. The revision-rate KPI still uses distinct activities backed by recorded return events in the selected proposal cohort; missing history is not treated as proof that no returns occurred. Dashboard and Analytics workflow statuses share distinct authority-based colored pills. Analytics retains compact desktop/mobile filters, server-side organization scoping and the disabled assigned-SO selector.

Office proposal controls use compact wrapping without changing their organization/college/period filters. **Every reviewing desk (OSO, SDO, OVCAA and OC)** rechecks the latest submitted packet and all required private files before endorsement or final approval. Missing packets, draft packets, missing/unreadable/empty files and incomplete latest versions block the transaction, including any plan-verification update or cash reservation. Historical approvals are not revoked automatically; missing evidence is explicitly flagged in their document panel. Complete ₱0 proposals remain valid.

Office calendars use **Today** and **Month** only on desktop and mobile; Agenda and continuation arrows are removed. **Ongoing now** and **Upcoming activities** appear below the month grid. Enter submits activity/venue search; clearing the search restores results without clearing scope/status/schedule or the selected month. Select filters still apply immediately, month links retain filters, and clicking a day/event opens actual schedule details. There are no Search/Clear action buttons.

The office **Notifications** bell opens a keyboard-accessible popover containing the ten most recent permitted workflow updates and persisted announcements. SO entries stay within the assigned organization; downstream desks cannot receive links to packages before their review stage. Links open the actual proposal or notice. This is a recent-updates list, not a fabricated unread counter; opening it does not change records.

Existing pagination controls consistently use **Previous · Page X of Y · Next**. Boundaries are disabled when shown; existing page sizes, empty/single-page visibility, filters, query strings and custom cash-page parameters are preserved. Server-rendered community, feedback, archive and cash pagers share `resources/views/components/pagination.blade.php`.

SO archiving: open **Archive**, create a folder, choose **Upload Document**, select that folder and a DOCX file, then submit **Upload & Archive**. File selection alone does not upload or save anything. Document cards open centered previews of the actual Word content; downloads preserve the original filename and bytes. Manual archiving does not submit or change AR/FR reports.

OSO Archive: search an organization, filter by academic year or college, then choose the eye-icon **View** action. Groups show preserved **AR & FR reports**, approved **Renewal records**, **Activity documents**, and **Other preserved files**, including nested files. Each document identifies its original filename and source folder/activity or renewal period; **View** uses authenticated original-file access. The redundant OSO **Folders & preserved files** browser is removed; SO folder creation and private uploads remain available. The shared preview shell retains **Close / Open in Tab / Download original**, without changing original downloads.

Archive KPI cards use the dashboard treatment above a compact year/scope/search toolbar and paginated organization grid. **View** opens the selected organization's grouped documents in a centered, scrollable dialog. Close, Escape and backdrop dismissal return to the same filtered directory page and restore the originating card's focus. Direct `archive_org` URLs also open the dialog; SO cannot select another organization's archive. Original **View / Download** access checks remain unchanged.

SO Budget Utilization: summary cards and charts precede **Record Expense**. Select an approved activity, attach up to three shared receipts, enter one or more item cards, then choose **Record expense** once. **Add item / Remove / Clear** manage the local batch; server validation, exact quantity × unit-cost totals, one-time cash posting and receipt seals remain authoritative. OSO organization search filters the five-row ledger directory without changing consolidated cash totals.

SO Accomplishment Report: **Create activity report** uploads a completed **DOC / DOCX / PDF** (up to 20 MB) for the selected academic year and semester. Upload saves a private draft; **Submit AR to OSO** is separate and immediately locks the submitted report. Only a returned-for-revision report can be changed afterward. Existing native activity reports remain editable where permitted; their generated packets retain evidence/completeness checks, while independent uploaded completed reports are not blocked by unrelated native drafts. AR/FR period selectors refresh automatically. OSO **View Submitted Reports** is available when either report has a readable submitted file and opens the available type.

SO Financial Report shows **Current Reports** followed by **Financial Report History**. Empty current reports offer **Create report**; history search/year/semester filters remain usable when empty. Creating new Financial Reports still requires the completed official XLSX workbook and an exact-file preview before submission. Viewing unsupported legacy Word files does not satisfy that submission gate.

SO Renewal: populated adviser/dean names start read-only. Use **Edit information → Save changes** only when they change. This saves names without reloading or discarding selected files; a names-only draft is not visible in OSO’s submitted packets. Valid local selections and actual saved files update the green checklist/readiness count. **Submit for Review** uploads the complete packet once and locks editing immediately. OSO **Return** or **Reject** reopens that organization’s packet while the filing window is open and the organization remains eligible; **Approved** stays final.

OSO Renewal uses dashboard-style KPI cards, inline icon/text notices and explicit action icons. Its **Open / Closed** switch is blue/on or gray/off from the persisted setting, never an optimistic toggle. After confirmation, `POST /office-desk/renewal/window` with `toggle_only=1`, current `window_id` and desired `is_open` updates submission access only; it does not save draft settings or alter applications/templates. Saved opening/closing dates still gate actual submissions, and the effective status identifies scheduled/ended windows. Cancel sends no mutation. **Save Settings** handles period, schedule and instructions separately and cannot reopen a closed window; new periods start closed and must be enabled with the switch. A stale window is rejected, SO cannot toggle, and the redundant right-side Renewal navigation badge is removed.

OSO Budget Utilization: the outlined eye + **View** action in the organization directory opens an organization-only activity dialog with the current reporting period, total approved budgets and total encoded expenses. Selecting an activity stays inside the popup and shows its scope, recorded payment methods, approved budget, actual spending, signed remaining balance, utilization, category breakdown, same-scale budget comparison and searchable expense items. **Back to activities** restores the list and focus. Year, semester, department and organization scope are preserved. Receipt **View** opens the existing private, read-only viewer over the details; multi-file navigation and unchanged-original downloads remain available, and closing the receipt returns to the selected activity.

Organization **encoded expense** totals sum eligible receipt item quantity × unit cost, including items awaiting a seal and excluding rejected/unposted items. Selected-activity **actual expenses** also retain earlier recorded spending; any difference appears as a separate chart category and explanatory note, never fabricated receipt rows. Zero approved budget displays **N/A** utilization, and overspending keeps its negative balance and percentage above 100% while only the visual progress bar saturates. Search does not change totals; receipt statuses and payment methods come from stored records. Cash balances and approval records remain unchanged. Close details, ×, Escape and backdrop dismissal return to the directory; short screens scroll content and the expense table while keeping close controls visible.

The cash directory uses uppercase table headers, compact organization search and a calculation note. A dash (**—**) means no saved cash account exists for that organization in the selected academic year; **₱0.00** remains a recorded zero. Approved budgets are independent of account existence. Totals still use saved accounts and actual cash transactions; changing the year does not copy historical balances, use legacy allocation totals as cash or fabricate missing accounts.

Read/detail and document-preview actions across student and office roles share the compact outlined eye + **View** treatment in `public/css/view-actions.css`. Context remains in accessible labels; downloads, editing, workflow decisions, navigation and password visibility controls retain their separate meanings. Existing URLs, preview handlers, organization access gates and unavailable-file behavior are unchanged.

OSO **Student Reports** shows persisted Total reports, Pending review, Acknowledged and Dismissed KPI cards above search, Status / College / Topic filters and a paginated concern queue. Selecting a concern opens the adjacent responsive detail/action panel with its actual text, student/program details, existing OSO remarks and confirmed acknowledgment/dismissal. Remarks are retained for the submitting student. All user-entered reports are preserved. The demo seeder no longer inserts feedback; only the two previously verified legacy seed examples were privately backed up and removed by exact stored identity.

Activity proposals select actual titles from the current approved renewal **Attachment I**, not from the blank template or separately typed titles/references. Choices are bound to the organization, approved source and file version; changed plans require reselection. Completed text-readable DOCX, PDF and legacy DOC plans supply choices; blank templates and image-only scans do not. **₱0** activities can complete final approval without an opening cash account or a cash reservation. Selected/saved filenames preview their actual bytes. Authorized office DOC previews convert privately, while Download original retains the DOC filename and bytes. Approved-plan viewers open the actual approved dossier file, with **Close** beside **Download original**.

The official **Waste Policy Compliance Form (WPCF)** is now supplied as `storage/Template/Creation Of Activties/In Campus Activities/Waste-Policy-Compliance-Form-2026 (1).docx`, genuinely converted from the supplied Word 97–2003 original. The catalogue uses this packaged source directly, not the ignored local `In Campus` junction. Creation-form downloads, the in-campus template ZIP and Updates use the modern editable DOCX. Official previews use the packaged PDF rendered from that DOCX so the university logo, floating graphics, form lines and checkboxes remain faithful without a runtime Docker conversion. The original DOC is retained locally for provenance, but is no longer an active template selector. Existing submitted files, their original bytes and review history are not rewritten.

Activity-creation document previews use a viewport-height dialog with a full-height flex container and a remaining-space document pane. Desktop previews use up to 90% of the viewport height; mobile previews leave a small screen gutter. Header and Close/Download controls stay visible while the document scrolls, including short landscape viewports. Percentage-height PDF frames no longer depend on an auto-height parent or a fixed minimum height.

SDO, OVCAA and OC use the same **Proposals** navigation label and icon as OSO, with role-specific review instructions and approval actions retained. Submitted document badges show the latest passed stage: **OSO approved** while awaiting SDO, **SDO approved** while awaiting OVCAA, **OVCAA approved** while awaiting OC, and **OC approved** after final approval. Returned/rejected states take precedence; display changes never write approvals. Private DOC, DOCX and PDF previews use actual submitted bytes, with **Close** immediately beside **Download original**. The SDO review checklist appears below **Submitted Compliance Documents**; its required acknowledgement belongs to the original **Endorse to OVCAA** form, and original stage/organization access gates are unchanged.

OSO **AR & FR Reports → Submission controls** uses separate AR and FR switches for the selected academic year and semester: blue/on means **Open**, gray/off means **Closed**. State is rendered from the persisted lock, never toggled optimistically. Switching off first opens a report-specific confirmation showing the period and **all-organizations** scope. Only **Yes, lock submissions** posts the selected lock; Cancel, Escape and backdrop dismissal leave server state untouched. Reopening also requires confirmation and does not change the other report type or neighboring periods. Existing submitted reports remain reviewable and SO can still prepare, preview and download while locked. The compact reporting-period controls and organization report directory preserve auto-apply, search and pagination; unavailable report actions are disabled, and no empty organization-detail card is rendered until an organization is selected.

The Reporting period controls remain inside a visible white report card, including tablet and phone layouts. OSO Approval Status and Transactions by Type charts use distinct maroon shades; their labels, values, reporting filters and matching approval legend are unchanged.

TOSA opens on **Applicants** with search, review-stage/program/year filters, pagination and a filtered roster export. **Requirements** separates the applicant’s A–G supporting-credential checklist from five official reference/template downloads. **Top10** is an academic-year-scoped shortlist ordered by finalized, verified total points, not GWA alone or an automatic award decision. Dossiers retain private original downloads, PDF previews and persisted stage, remarks and GWA review. Drafts and never-submitted applications are excluded from office lists, counts, exports and direct document/review access. Student submission requires all seven readable, nonempty, correctly formatted, owner-bound private packet sections—not merely upload metadata. Previously submitted records remain visible if an original later becomes unavailable; opening that file reports an error. No missing-document counters or fabricated TOSA dashboard/audit data are shown.

The five official **references/templates** are **Front Page.doc**, **Guidelines TOSA 2025.pdf**, **TOSA Application Form 2025.docx**, **TOSA COMPUTATION.xlsx**, and **TOSA Submission Guide.doc**. They are not five mandatory student uploads. The guide’s **“Nomination Application Form” is the same TOSA Application Form 2025.docx**, not an additional form: download it, complete it with the required photo/signatures, and submit the completed version in section B. The packet order is **A** labeled cover/table of contents, **B** completed TOSA Application Form, **C** profile/résumé/CV, **D** good moral certificate, **E** academic evidence, **F** leadership evidence, and **G** social-responsibility evidence. Academic evidence includes Registrar-certified grades with GWA and evidence for claimed awards/participation; leadership includes positions/awards and certified labeled supporting copies; social responsibility separates school and non-school activity evidence.

The portal’s digital uploads accept **PDF** for supporting sections and **PDF or DOCX** for the completed nomination, up to **25 MB per file**. These are platform upload formats, not a claim that the paper guide requires PDFs. Choose or replace files, preview them, then click **Submit for Review once** to save the whole packet and send it to OSO. File selection alone does not save or submit anything. Guides, the blank nomination and the computation workbook are references, not supporting credentials. File-format/readiness checks do not verify signatures, extract GWA or certify achievements; scoring uses evaluator-entered and verified evidence, not automatic document interpretation. No filing deadline is inferred from the guide’s inconsistent 2024/2025 dates.

Student TOSA uses compact, keyboard-accessible collapsible sections with one maroon accent: Submissions, application status, preparation guides, A–G supporting credentials and submitted lock information. The student page does not display the personal score/calculation section or precompute its ranking data; evaluator scoring, qualification ranking and workbook endpoints remain unchanged. The official application-form card and preparation library start expanded; other sections start collapsed, while Revision opens status/remarks and credentials automatically. Submission errors and native file validation reveal the credentials section. Reference cards show only their title and consistent **View / Download** controls, use two columns on larger screens and stack on narrow phones. PDF and DOCX templates, saved originals and local selections open the same centered, responsive viewer used by Renewal; clicking a filename opens its preview, and original downloads retain their names and bytes. Word pages keep their original width with scrolling on small screens. **Open in Tab** is available for PDFs/images, not native Word downloads. **Front Page** and **TOSA Submission Guide** DOC references use the existing isolated Docker converter for temporary, private PDF previews of the currently resolved official file, including a valid OSO-uploaded replacement. Generated workspaces are released after streaming; conversion failure reports an error without changing the source or blocking its original DOC download. Students cannot upload arbitrary files to the office conversion endpoint. The computation XLSX opens the existing private, read-only worksheet viewer. Selecting files does not save them individually: the single **Submit for Review** saves and submits the complete packet.

TOSA has no per-file Save/Delete buttons or mutation endpoints. Selected files stay local until the single multipart `POST /portal/tosa/submit`; their compact cards show a previewable filename, format/size, **Replace file** and **Cancel selection**, without “Selected · Not submitted” text. A selection hides conflicting saved/missing-file details and the saved original’s Download control; cancelling restores the actual saved original and its download, or the missing-file state. Disabled submission has keyboard-accessible missing/invalid-section guidance. Each submission requires all A–G sections as valid selections or actual saved private originals; invalid choices must be corrected or cancelled. Cancellation clears only that local selection, and saved-file downloads stay bound to saved bytes. Validation, storage or database failure preserves the previous packet and review and removes newly written files. **Successful submission immediately locks the application. Only Revision unlocks editing and resubmission:** the review desk displays the existing persisted `subsection=returned` stage as **Revision**. Students see reviewer remarks, replace the affected files and click **Resubmit for Review**; unchanged originals carry over. Resubmission returns the packet to **Screening** and locks it again, clearing stale GWA verification, review remarks and score evaluation when evidence changes. Screening, interview, rejected and accepted applications remain read-only; accepted records cannot bypass the lock through conflicting revision metadata. The server rechecks the persisted stage, including requests from stale revision pages. View and Download remain available in every stage. No applicant data reset is performed. Authorized OSO replacement references use `POST /office-desk/tosa/references/template` with `reference_key` and `template_file`; source-ID downloads retain bundled originals and record-ID downloads retain the requested upload. Historical metadata and bytes remain preserved without reclassifying guides/workbooks as evidence or old grades/certificates as complete bundles.

**Evaluator scoring:** authorized OSO/OVCAA evaluators enter GWA and evidence-referenced academic awards, leadership seminars/positions/awards and school/non-school social activities. The server computes **100 points maximum**: grades 30, academic awards 10, leadership 30 and social responsibility 30, with each criterion’s category caps and exact integer milli-points. **Save provisional** exposes a draft calculation only. **Finalize verified score** requires verified GWA from 1.00 to 3.00, verified eligibility, a consecutive academic year and all seven original credentials. Scoring does not accept the application or award TOSA automatically.

The TOSA desk uses real submitted-applicant Total / Pending / For revision / Evaluated cards; Evaluated means a saved scoring evaluation, not automatic acceptance. Qualification review remains separate from the evaluator scoring dialog. Its header/footer stay visible while the evidence body scrolls, including phone layouts. Academic year and GWA start read-only; authorized evaluators must choose the explicit edit action before correcting them. Saving relocks them, and opening scoring uses the current reviewed grade. Read-only clearance cannot edit or submit, and a late save for one applicant cannot overwrite another applicant's open dossier or score form.

**Ranking:** only accepted applications with finalized, verified eligible evaluations and matching reviewed GWA rank within their academic year. Equal totals share competition ranks; every candidate tied at rank 10 remains visible, and a tie crossing the ten-place cutoff requires committee resolution rather than an arbitrary name/GWA tiebreak. Drafts, unverified inputs and malformed/stale stored calculations never rank. Changed grades/verification or Return/Reject make the retained calculation provisional; changed student evidence clears the evaluation and requires a fresh review. Score and qualification writes recheck the persisted packet under a database row lock.

**Student calculations and Excel:** students see only their own provisional/finalized breakdown, raw points, applied caps, evidence entries and eligible rank. The original computation reference and a separate personal **SUMMARY / Acad / L1 / L2 / L3 / SC1 / SC2** workbook each support read-only preview and XLSX download. Personal exports contain saved inputs and actual server-computed points, not template demonstration achievements or evaluator identity. Original formulas/cached values display as inert text; neither preview executes imported formulas. Generated text cells remain literal strings, and external workbook relationships/entities and oversized packages are rejected.

**Rubric corrections:** `tosa-2025-corrected-v1` follows the stated criterion rates. The bundled original’s `SUMMARY!F20` incorrectly points to social-responsibility `SC1!H216`, and `L3!H82` uses national position rates instead of the local rates stated in `L3!H77`. System scoring uses the correct local leadership components/rates and verified attended seminar days from 1 to 5, not erroneous date subtraction. The bundled workbook’s sample labels and cached points never establish an applicant’s achievements. Original workbook bytes remain unchanged.

For whole-packet uploads, allow at least seven files per request and 25 MB per file. Configure PHP `post_max_size` and any reverse-proxy request-body limit to **256M or higher** to accommodate seven maximum-sized files plus multipart framing.

### Student portal (`/portal`, `student.auth` middleware)

| Page | Route | What it does |
| --- | --- | --- |
| Home | `GET /portal` | Welcome overview, budget snapshot, upcoming and recent activities |
| Community | `GET /portal/community` | Social feed: posts (text/photo, optional activity link), likes, comments |
| Post / like / comment | `POST /portal/community/posts`, `POST /portal/community/posts/{post}/like`, `POST /portal/community/posts/{post}/comments`, `DELETE /portal/community/posts/{post}` | Community actions for the logged-in student |
| Community photo | `GET /portal/community/posts/{post}/image` | Active student session plus the post’s public/college/organization audience check; private, no-store image response |

The student Settings menu contains read-only official Profile settings with Close only, not the unused Compact view toggle or a Save/Cancel form. Corrections go through the registrar; `PUT /portal/profile` rejects student edits. Budget Category Breakdown uses compact **In-Campus / Off-Campus** rows calculated from final-approved activities, including each scope’s actual spending and approved budget.

The student sidebar identifies the **BatStateU Student Portal** and uses bold navigation labels. Activities, SDG Highlights and budget transparency are restricted to the student's recorded department, including recognized college aliases; query parameters cannot widen that boundary, and an unmapped department receives no activity or budget records. Final-approved off-campus activities, including local-off-campus records, contribute to the Off-Campus breakdown. Searchable SDG cards open related department activities without a redundant department/status filter. **Student Voice** includes the submission form and owner-only expandable history with saved OSO remarks. Timelines use recorded submission/acknowledgment times; an unrecorded dismissal time is explicitly unavailable rather than inferred from a mutable update time.

Student activity details no longer display unsupported year-demographic charts, QR/ledger participation attestations or the browser-only RSVP/certificate panel. Existing activity tabs and stored registration records remain unchanged.

Announcements, dashboard updates and notification links use the same persisted publication metadata. General, Deadline/Reminder, explicit TOSA and other OSO notice types map to their respective bulletin filters; body keywords do not override the chosen publication type. Announcement links open the requested notice. The office notice popup footer contains **Close** only; attachment viewing/downloads and existing acknowledgment receipts remain intact.

New announcements require a future **Expiration date & time** in the configured application timezone (Asia/Manila on the pilot). Publish stays disabled until the type, priority, nonblank details and expiration are valid; the open composer also disables itself when that expiration passes. New types are General Announcement, Deadline Notice, Policy & Guideline and Compliance Reminder; legacy Official Notice records remain readable. On subsequent feed requests, expired notices are excluded consistently from office/student dashboards, bulletins, notification feeds and Updates. Expiration never deletes a notice or its existing acknowledgment/download records; pre-existing notices with no expiration remain published.

Supporting attachments are optional. If selected, a file must be nonempty, one of the supported PDF/Word/Excel/image formats, and no larger than **15 MiB (15 × 1024 × 1024 bytes)**. Invalid selections are cleared while their validation notice remains visible and publication stays disabled until the selection is corrected or explicitly cleared. The disabled Publish action is gray. Text-only notices are allowed; cancellation of the shared publication confirmation preserves the draft. The server independently validates these rules before writing files or announcement rows.

The announcement and template directories paginate independently at ten filtered items per page; grid/list views share each directory's page, and search/category changes reset that directory to page one. Announcement links can open notices outside the current page. Notice **View** reads the saved text. File **View / Download** actions appear only for an actual readable, nonempty attachment; text-only notices no longer generate substitute Word documents. The obsolete generated-notice document route is removed. Attachment responses preserve original bytes and Unicode filenames, support authenticated inline preview, and return 404 for missing/empty files rather than a response-type error.

Uploaded-template counters retain their saved history. Bundled official templates now persist catalogue download counts in `office_template_downloads`; tracking begins with this update, without inventing earlier totals. Successful original-download GET requests increment the appropriate counter atomically; preview/HEAD requests and unavailable sources do not. Counts are request totals, not confirmation that a client completed a transfer. Apply the additive counter migration before serving this batch; no existing announcements, templates, renewal applications or archive files are reset:

```bash
php artisan migrate --path=database/migrations/2026_10_20_000002_create_office_template_downloads_table.php --force
```

Before serving the announcement-expiration update, back up the application databases and apply the additive nullable-column migration (existing notices are not reset):

```bash
php artisan migrate --path=database/migrations/2026_10_20_000001_add_expiration_to_office_announcements_table.php --force
```

Directory Copy Link icons still copy the actual notice URL using the Clipboard API, a copy-command fallback, then a manual-copy prompt if browser permissions block both. There is no Copy Link or acknowledgment action in the notice popup footer.

Student feedback is acknowledged or dismissed by OSO. **Acknowledge report** preserves the first reviewer/time, appears in the submitting student’s history and produces an owner-only notification; dismissal is not acknowledgment. Existing `verified` storage fields remain internal persistence details. Apply `2026_10_15_000001_create_office_announcement_acknowledgments_table` before serving the updated office announcement page; no record reset or seeding is required.

Community feeds show actual persisted reaction/comment counts, including zero, without sample posts/comments or decorative placeholder reactions. All seven supported reaction types remain available. The submitted audience controls visibility; organization-only publishing requires membership. `community_q` searches visible post text, authors and linked activity titles before pagination, retaining the selected sort. Success messages disappear after four seconds; validation errors remain visible.

Business submissions, reviews, deletions and settings changes require confirmation; existing dedicated lock/logout dialogs are not duplicated. Cancel preserves drafts and selected files without sending the mutation. Read-only navigation, previews, downloads and local file selection/cancellation do not prompt. Logo/template file selection is staged until the explicit upload/save action.

### Voting system (`/voting-system`)

Integrated official voting module (admin, voter flows, Google OAuth, canvassing) served through `App\VotingSystem\Kernel` under the `/voting-system` prefix.

Voting pages use the original `VotingSystemOfSSC` teal, sage, cream and orange design with full-width gradient headers, warm backgrounds, original cards and Public Sans/Outfit typography. OrgChain branding, mounted routes, privacy consent, mobile ballot drawer/review dock and public blockchain ledger/receipt features are retained. The separate staff sign-in design is unchanged.

Voting admin and canvassing dashboards prioritize voter totals and live results above the per-program breakdown. Recent Submissions is removed from both dashboards. The canvassing tally respects the selected college filter; voting records, authentication and ballot submission are unchanged.

## Authentication

- **Students** (`student` guard, `App\Models\UserAccount` on the `orgchain` connection, keyed by `sr_code` with `full_name`, `college`, `program`, `year_level`):
  - SR Code login with an emailed verification code (`POST /student/login/code`, `POST /student/login/verify`).
  - Continue with Institutional Account via Google OAuth (`GET /student/auth/google`, `GET /student/auth/google/callback`), restricted to the BatStateU Google domain.
- **Offices** (`office` guard, `App\Models\OfficeUser` with roles `so`, `oso`, `sdo`, `ovcaa`, `oc`): BatStateU email + password on the private login path configured by `OFFICE_LOGIN_PATH`. New and reset accounts must replace their temporary password at `/office-desk/password/change` before viewing data or using other office actions. Existing accounts are not forced to change their password by the migration.

### OSO account management and officer turnover

Open **Settings → Users & Roles** as OSO. Search by name, email, organization, role or title.

- **Add Officer Account:** choose the role, enter the officer’s own official institutional email and a confirmed temporary password. SO accounts require a registered organization and have no TOSA access. Share temporary credentials privately; the officer must set a different personal password on first login.
- **Edit profile:** update the officer’s name, email, title or employee ID. Role and assigned organization remain immutable. An email change revokes existing sessions and remembered login.
- **Reset temporary password:** choose a different password from the current one. Old credentials and sessions stop working; the user must change the replacement password before accessing the desk. Resetting a disabled account does not enable it.
- **Turn over SO officer:** select the active outgoing representative and enter a separate incoming account’s name, unique university email and confirmed temporary password. The incoming account inherits only the same organization assignment, not the outgoing identity. Saving atomically creates the incoming account and disables the outgoing one. Concurrent or repeated turnover cannot create multiple replacements.
- **Disable / Enable:** toggle another account’s access without deleting its identity or history. Revoked sessions stay invalid after re-enabling; a fresh login is required. Use **My Account** for your own profile/password rather than administrative account actions.

Turnover retains organization activities, cash ledger, AR/FR, renewal and archive records. Past officer identities remain stored; do not rename the outgoing account to the new officer or share the outgoing password. Deans and advisers have no separate login role unless a defined review workflow is added.

## Key data

- Default `mysql` connection (`DB_DATABASE=orgchain`): all office, organization, activity, budget, report, renewal, archive, TOSA, student-account and community tables. Cross-feature transactions share this connection.
- Dedicated `voting` connection (`VOTING_DB_DATABASE=votingsystem`): elections, positions, candidates, voters, ballots/receipts, voting staff and voting audit/security tables. Student sign-in reads the election roster through this connection; it never writes voting data into the core database.
- `legacy_*` tables in `orgchain` preserve older unused prototype/student/community records. They are archived data, not active model targets; do not merge their IDs into current accounts or posts by guesswork.
- Schema changes run through Laravel migrations, not voting web requests or a `schema.lock` marker. Fresh installs create active student accounts and community tables only; the account baseline preserves pre-existing imported accounts on rollback, and organization membership points to the active registry. The candidate-photo migration widens undersized MySQL BLOB columns to accommodate the existing 2 MB upload limit without shrinking larger columns.
- Legacy document records may retain `public` disk paths, but confidential storage prefixes are denied by the supplied Nginx, Apache and development-router rules; authenticated document endpoints enforce ownership and workflow stage. New TOSA, community-photo and manual archive uploads use the private `local` disk. Community images, including preserved existing files, are served only through the audience-checked photo route; direct `/storage/community/` access is denied. `archive_documents.file_disk` preserves legacy disk selection.

### Existing mixed-database cutover

Enter maintenance mode before moving the old mixed schema. `scripts/separate-databases.php` takes an operation and an existing private directory under `storage/app/private/backups`. Run `backup` first: it creates complete SQL dumps, ordered-row fingerprints, a table-move manifest and reverse rename SQL. Keep the original database-name configuration until this backup is captured.

Run `restore-copy`, then `move ... --copy` and `verify ... --copy` using that same private directory to rehearse on disposable databases. Verify their foreign keys stay within the disposable pair. Deploy the connection/migration changes and rehearse migration upgrades there before the live operation.

Run `move` without `--copy` only in maintenance mode after successful backup restoration. It atomically renames non-voting tables into `orgchain`, preserving IDs and row bytes; colliding obsolete tables receive `legacy_*` names. Then set `DB_CONNECTION=mysql`, `DB_DATABASE=orgchain` and `VOTING_DB_DATABASE=votingsystem`, remove `DB_ORGCHAIN_DATABASE`, clear the config cache, and run `php artisan migrate --force`. The central migration history belongs to `orgchain`; voting migrations explicitly target `voting`. Run `verify`, rebuild the config cache and smoke-check both portals before `php artisan up`. Keep the private backups; do not reset or reseed either live database.

## Source control and private configuration

Keep `.env`, credentials, signing keys, database dumps, uploaded records and populated report examples out of Git. The local real-student TOSA seeder, supplied accomplishment reference PDF and populated CARS renewal examples are excluded; blank official templates remain available. Untracking a file preserves its local copy but does not erase older Git history. Previously published credentials require coordinated rotation, and historical private records require a separate history-cleanup plan.

Account seeders have no public password defaults. Configure `OFFICE_SEED_PASSWORD`, `STUDENT_DEMO_PASSWORD`, `VOTING_ADMIN_SEED_PASSWORD`, `VOTING_CANVASSING_SEED_PASSWORD` and `SYSTEM_ADMIN_PASSWORD` privately before running the corresponding seeders. Demo student and registry identities are synthetic. Never run destructive development database resets against user data.

Configure `BLOCKCHAIN_NODE_SECRET` privately and consistently across the participating nodes. The slim node also accepts `ORGCHAIN_NODE_SECRET`; it refuses startup without a secret, and the receive API rejects missing server configuration or mismatched tokens. The PowerShell launcher requests a private secret without echoing it or saving it in its connection handout. Office smoke scripts require private `E2E_OFFICE_PASSWORD` configuration.

For the announcement/community regressions, use disposable schema-only core and voting databases, set `ORGCHAIN_TEST_DATABASE` and `ORGCHAIN_TEST_VOTING_DATABASE` explicitly, apply migrations, then run `php artisan test tests/Feature/AnnouncementFeedbackTest.php tests/Feature/CommunityReportIssuesTest.php`. Never point these variables at production or user records.

## Tech stack

### Core Application Stack
- **Framework & Backend:** [Laravel 11 / 12](https://laravel.com), PHP 8.2+, Blade Templating Engine
- **Frontend & Tooling:** [Vite](https://vite.dev), [Tailwind CSS](https://tailwindcss.com), Vanilla JavaScript, Bootstrap Icons
- **Relational Databases:** MySQL 8.0 (Dual-schema architecture: `orgchain` for student & community portal; primary database for office desk, activities, and elections)

### Blockchain & Cryptographic Layer
- **Enterprise Ledger:** [Hyperledger Besu](https://www.hyperledger.org/projects/besu) (Linux Foundation)
  - **Consensus Protocol:** QBFT (Quorum Byzantine Fault Tolerance) multi-validator consensus
  - **Gas Economics:** Configured with zero gas fees (`gas_price = 0`) for perpetual, zero-cost state university operations
  - **Network Topology:** Private enterprise permissioned consortium network (Dockerized validator nodes)
  - **Smart Contract Layer:** Solidity smart contract (`OrgChainAnchor.sol`) for anchoring voter cryptographic receipts and financial expense liquidations
  - **RPC Bridge:** JSON-RPC over HTTP (`web3p/ethereum-tx` / custom RPC client) connecting Laravel to the Besu ledger
- **Voting Ledger Engine:** VoteChain 3-node fault-tolerant SHA-256 JSONL hash-chained audit ledger with receipt verification codes

### Document Processing & Preview Engine
- **In-Browser Document Preview:** Self-hosted `docx-preview.js`, `jszip`, and browser PDF view engines for reviewing submitted requirements without external software
- **Template System:** BatStateU official university ISO forms (`BatStateU-FO-SOA-01 Rev. 03`, Attachments A–J) in native OpenXML `.docx`, `.pdf`, and `.xlsx` formats
- **Compliance & Signing:** Physical wet-ink signature scan-and-upload workflow in compliance with the **Data Privacy Act of 2012 (RA 10173)**

### Authentication & Security
- **Student Auth:** Institutional Google OAuth 2.0 (restricted to `@g.batstate-u.edu.ph`) + 6-digit email OTP verification codes
- **Email login flow:** Enter the complete SR Code (`YY-XXXXX`), then Send Verification Code automatically opens the email-code step and focuses its six-digit input. Invalid submissions and explicit login links reopen the modal after its handlers initialize; no second Login click is required.
- **Office Desk Auth:** Role-based access control (RBAC) supporting `SO`, `OSO`, `SDO`, `OVCAA`, and `OC` desks on obfuscated administrative paths
- **Transport Security:** Cloudflare edge HTTPS with a loopback-only Nginx origin reached through an authenticated tunnel.

### Production Infrastructure & Hosting
- **Pilot Environment:** Hostinger KVM 2, Ubuntu 26.04 LTS, 2 vCPU, 8GB RAM and 100GB NVMe storage. The installed distribution PHP 8.5 runtime passes the locked production dependency requirements.
- **Containerization:** Docker & Docker Compose for four memory-limited Besu validators and isolated document conversion.
- **Web Server:** Native Nginx and PHP-FPM; MySQL 8.4 hosts the existing two application databases.


## Local setup

```bash
composer install
npm install
```

Build the private office-document converter once on the host running the application:

```bash
docker build -t orgchain/document-converter:local infra/document-converter
```

The PHP runtime needs Docker CLI/daemon access and writable private storage. Docker daemon access is host-admin privileged: restrict the runtime account and never expose the socket publicly. If Laravel itself runs in a container, conversion bind-mount paths must exist at the same absolute paths on the Docker host. `OFFICE_DOCUMENT_CONVERTER_IMAGE` and `OFFICE_DOCUMENT_DOCKER_BINARY` configure the image/CLI. Conversion runs without network access, with macros disabled, resource/time limits and private per-job workspaces cleaned after completion; missing Docker/image support returns an explicit preview error rather than substituting a blank template.

Create `.env` and set at minimum: `DB_CONNECTION=mysql`, `DB_DATABASE=orgchain`, `VOTING_DB_DATABASE=votingsystem`, `DB_USERNAME`, `DB_PASSWORD`, `OFFICE_LOGIN_PATH`, Google OAuth (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, `GOOGLE_ALLOWED_DOMAIN`), mail credentials for login codes, and `TINYMCE_API_KEY`. Voting has independent optional `VOTING_DB_HOST`, `VOTING_DB_PORT`, `VOTING_DB_USERNAME`, `VOTING_DB_PASSWORD` and `VOTING_DB_URL`; do not point it at the core database.

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

## Security deployment and existing-file cutover

Student login codes are delivered only to the institutional mailbox. No response, session diagnostic, or E2E cache exposes a plaintext code. Authentication endpoints apply per-IP and per-account limits; student code requests use the same response for known and unknown accounts.

SO accounts require an active organization assignment. Receipt compilations derive the organization from the account, ignore forged organization filters, and reject foreign activity IDs. Announcements and organization reminders require OSO authorization. TOSA has no separate PIN gate: active OSO accounts receive full access through their role, including accounts with legacy “No Access” clearance. OVCAA access follows its assigned read-only/evaluator clearance; other offices remain blocked. Login, account inactivity, credential revocation, and required password changes still apply.

TOSA scoring needs the additive nullable `score_evaluation` JSON column on the primary `mysql` connection. For an existing deployment, back up the database, deploy the updated code and run the targeted migration with the correct private database configuration:

```bash
php artisan migrate --path=database/migrations/2026_10_11_000001_add_score_evaluation_to_tosa_applicants.php --force
```

This does not reset submissions or import demonstration scores. XLSX preview/export uses the existing PhpSpreadsheet dependency and needs no additional Docker service.

Confidential activity attachments, renewal/report documents, receipts, archives, and TOSA requirements use the `local` disk under `storage/app/private`. Serve them only through authenticated, ownership-checked controller routes. The PHP router and Apache configuration deny static `/storage` access except the explicit public asset categories; an Nginx deployment must apply the same allowlist before its static-file handler. Never alias the private directory into the document root.

Back up both databases and both storage disks before migrating an existing installation. Stop concurrent uploads and run:

```bash
php artisan down
php artisan documents:privatize --dry-run --diagnostics
php artisan documents:privatize --diagnostics
```

The command verifies SHA-256 hashes, refuses symlinks/unsafe paths and conflicting bytes, updates disk metadata, and only then retires verified public originals. Reruns are safe. Failure diagnostics contain confidential paths and belong only in `storage/app/private/migration-diagnostics/confidential-documents.json`; do not publish or commit them.

If originals were already missing, `--allow-missing --diagnostics` migrates available verified files while preserving missing-file metadata. It still exits nonzero until those originals are restored. It never overrides collisions or unsafe paths and never manufactures replacements. Restore missing files from a verified backup to the expected private paths and rerun. Filename matches alone do not establish ownership.

Before reopening public access:

- Set `APP_ENV=production`, `APP_DEBUG=false`, and `SESSION_SECURE_COOKIE=true`; ensure inherited process variables do not override these values. Cache configuration with `php artisan config:cache` in that production environment. Trust only actual proxy addresses via `TRUSTED_PROXIES`, not every client.
- Purge **all** existing Cloudflare confidential-file cache entries using **Purge Everything**, then verify old URLs without cache-busting return denial. Origin maintenance mode, file removal, and new cache headers cannot erase existing edge copies. Do not cache authenticated pages or login responses; configure HTTPS/HSTS at the edge as appropriate.
- Configure trusted CA bundles for PHP OpenSSL/cURL. SMTP SSL/STARTTLS and remote blockchain node requests verify certificate chains and hostnames; do not disable verification to bypass deployment errors.
- Rotate every credential exposed in historical Git commits at its provider, including OAuth, mail, editor/API, blockchain-node and signing credentials as applicable. Coordinate Git-history removal with all clone owners; removing files from the latest tree does not remove historical copies. Do not rotate the application encryption key without a data/session migration plan.
- Verify authorized document downloads, foreign-organization denial, mailbox login, node authentication, and `composer audit` / `npm audit`, then run `php artisan up`.

The local remediation migrated 63 available originals and retired 59 public copies; 32 pre-existing missing references remain in the private diagnostic manifest. For the VPS pilot, the owner approved preserving these historical records with unavailable originals rather than deleting them or inventing replacements. The owner completed Cloudflare Purge Everything; a real private original's former static URL returns 404 without cache-busting. The pilot is publicly reachable without a network allowlist. External credential-rotation confirmation and a complete real mailbox login remain outstanding operational checks, not verified claims.

### Hostinger KVM 2 pilot deployment

`infra/vps/` contains the deployed service configurations. The application lives at `/srv/orgchain/current`; Nginx listens only on `127.0.0.1:8000`, preserves the public-storage category allowlist and denies other PHP entry points and dotfiles. The PHP-FPM pool allows three workers with a 256MB per-request limit and enforces Secure/HttpOnly/SameSite cookies for native PHP voting sessions as well as Laravel's configured sessions. MySQL uses a 256MB buffer pool and an explicit `+08:00` session default to preserve the source application's timestamp interpretation after UTC-safe dump/restore. A 2GB systemd-managed swap reserve handles bursts, not extra advertised capacity.

Start the configured blockchain with its persisted keys, genesis, contract metadata and runtime data. Never regenerate it during an ordinary restart:

```bash
cd /srv/orgchain/current
docker compose --project-directory infra/besu --env-file infra/besu/.env \
  -f infra/besu/docker-compose.yml -f infra/vps/besu-vps.override.yml up -d
```

The VPS override pins the approved Besu image, runs validators as UID 1000, limits each to 1GB RAM and half a CPU, rotates container logs, and replaces all published ports with loopback-only RPC. Validator P2P stays inside Docker. Four validators on one VPS do not provide independent-host fault tolerance. With the owner's approval, the old known-key thesis chain was archived privately and a new random-key pilot chain was created with chain ID `20261015`. The existing vote proof's transaction and receipt remain unchanged; verification labels a different-deployment proof Historical instead of falsely claiming either current-chain verification or tampering.

Build the existing `infra/document-converter` image on the VPS. Set `OFFICE_DOCUMENT_DOCKER_BINARY=/usr/local/bin/orgchain-document-docker`; the launcher admits one converter at a time with a five-second lock wait, within the application's process-timeout grace. Its lock directory is recreated by the supplied tmpfiles rule. The PHP account's Docker access is host-admin-equivalent; keep its code and environment read-only, restrict private storage and never expose the daemon socket. Application logs use the supplied rotation rule.

The dedicated Cloudflare service uses a restricted system account and `/etc/cloudflared/orgchain-tunnel.json`, never a credential committed to this directory. The public hostname uses the VPS connector; the verified OrgChain-only laptop service is stopped and disabled. Never run both connectors against independent databases. The mandatory `/etc/nginx/snippets/orgchain-pilot-access.conf` retains private/no-store response headers but no network/IP restriction: pilot users can reach the site from Wi-Fi or mobile data. Laravel authentication, role/ownership authorization and Nginx's private-storage denial remain enforced. A laptop connection is not required to host the site.

Preserve `APP_KEY`, use a separate database account scoped to the two restored schemas, and do not run seeders, reset commands or `key:generate`. Keep SQL/file/chain backups outside the repository and web root with verified checksums and owner-only access. A rollback to the laptop requires the latest VPS data before reconnecting its tunnel, not the old pre-cutover database.

The public landing page supplies Open Graph title, description, canonical URL and the existing logo for Messenger/Facebook previews. A previously shared 403 card can remain in Meta's separate scraper cache after origin access is corrected. Use [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/), enter `https://orgchain.tech/` and request Scrape Again; a Cloudflare cache purge does not invalidate Meta's stored preview, and old chat cards may not update.

### Independent public status website

[status.orgchain.tech](https://status.orgchain.tech/) is hosted on GitHub Pages, independently of the application VPS, from [chals1029/orgchain-status](https://github.com/chals1029/orgchain-status). The landing-page footer links to it. Its logo is hosted with the status site, not fetched from the VPS.

Upptime checks the public landing page and Laravel's `/up` endpoint, plus six session-free readiness signals: application databases, blockchain, private storage, document processing, institutional sign-in gateway and mail-provider connectivity. These do not claim a completed sign-in, delivered email, successful transaction or successful private document submission. Monitoring history starts when each component is first observed; no earlier uptime is fabricated.

The `OrgChain Status` workflow schedules availability checks and publication every five minutes; GitHub schedules can be delayed. It publishes a custom static interface and same-origin `status.json`, so visitors do not need GitHub API access. The two-column service grid provides a 90-day UTC timeline, daily details, maintenance notices and incident history without a provider footer. Days before monitoring began remain gray; partial-day uptime uses only observed minutes, and overlapping outage intervals are counted once. Checks or snapshots older than 15 minutes, future dates and failed refreshes are explicitly delayed/unavailable, not falsely operational. Response-time recording remains daily or on manual/configuration-triggered runs. If results appear stale, inspect that repository's Actions runs. No additional VPS is needed: public hosting stays independent while internal probes run on the existing application VPS timer.

Monitoring uses only the repository-scoped, short-lived `GITHUB_TOKEN`, with separate permissions for monitoring, site building and Pages deployment. No application credentials, SMTP/OAuth secrets, private documents, student records or broad personal access token are stored in the public status repository. Cloudflare DNS has a DNS-only CNAME from `status` to `chals1029.github.io`; GitHub Pages enforces HTTPS.

Detected outages open `status` issues and recovery closes them; short incidents are retained. Publish planned maintenance through an issue labeled `maintenance`, using Upptime's UTC `start`/`end` comment metadata and `expectedDown`/`expectedDegraded` component slugs matching `.upptimerc.yml` in the status repository. Keep incident text public-safe; never paste raw application logs or student information.

The private `orgchain-health.timer` runs `php artisan system:check-health` as `www-data` every five minutes. Install its supplied service/timer from `infra/vps/`, provision `storage/app/private/system-health`, its `probes` child and `storage/app/private/office-document-converter` as real non-symlinked directories owned by `www-data` with mode 0700, refresh the production configuration cache after deploying `config/system-health.php`, and enable the timer. The existing converter's serialized Docker access remains host-admin-equivalent and its resource limits remain enforced.

The command holds an exclusive lock, then atomically stores only allowlisted states and first/latest measurement timestamps in the private snapshot. `/health/services` and `/health/services/{allowlisted-id}` only read sanitized cached observations; anonymous requests cannot launch probes, start sessions or query databases. Missing, corrupt, future-dated and older-than-15-minute observations become unknown. Individual operational/degraded signals return HTTP 200; unavailable/unknown signals return 503. The status publisher reads actual probe timestamps separately, so a fresh HTTP poll cannot make an old internal observation appear fresh. Historical availability describes readiness-signal availability, not an internal failure diagnosis.

Database probes use isolated configured connections and `SELECT 1`, not student rows or schema migrations. Blockchain probes require the active Besu driver, expected chain ID, recent blocks and deployed contract code; all configured validators healthy is operational, retained quorum with a failed validator is degraded, and quorum loss is unavailable. RPC addresses, node counts, blocks, contract addresses and signer material are never returned publicly.

Private storage uses a unique write/read/delete roundtrip. Document processing converts a unique synthetic RTF to PDF and verifies its marker through extraction, then releases all owned workspaces; real submissions are never used. Google checks validate the configured public callback shape and fixed provider discovery availability, not real login or client-secret validity. Mail checks both configured SMTP pathways with mandatory verified TLS and available authentication, followed by QUIT, without MAIL/RCPT/DATA or message creation. Neither a successful probe nor its process exit code proves student workflow success.

Inspect `systemctl status orgchain-health.timer orgchain-health.service` privately and invoke `sudo -u www-data php artisan system:check-health` from the application directory for a manual refresh. Preserve the private snapshot between deployments so component start times remain truthful. Never commit this snapshot, configuration caches, lock/probe files or credentials.




### Safe regression and browser checks

For disposable MySQL schemas, set `ORGCHAIN_TEST_DATABASE` for the core database and `ORGCHAIN_TEST_VOTING_DATABASE` for voting. Use schema-only databases, not copies of real student rows, for functional tests. Override `LARAVEL_STORAGE_PATH` and `APP_CONFIG_CACHE` to private temporary paths, copy required official `storage/Template` sources into that isolated storage root, and set `BLOCKCHAIN_DRIVER=file` for file-ledger checks. Never run destructive storage tests against live uploads.

Apply all migrations to the explicitly configured disposable pair before testing; do not clone an outdated schema and assume the new tables and columns exist. The main migration ledger is on the core connection, while voting migrations route to `voting`. On Windows, use project-relative `APP_*_CACHE` paths into ignored private storage: Laravel's cache-path resolver does not recognize a drive-letter path as absolute.

`node --test tests/Unit/calendar-charts.test.mjs` executes the actual dashboard/analytics aggregation functions using the complete server reporting-metadata shape. Private-file symlink and unreadable-file checks need a native Linux filesystem and a non-root user to exercise POSIX permissions; Windows skips and root bypasses are not passing evidence. Keep their skip guards, and record the Linux execution separately from the Windows suite.

`php artisan test --compact` runs the backend suite. `npm run build` builds browser assets. The interactive `scripts/auth-otp-smoke.mjs` accepts only a code actually delivered to the institutional mailbox; it must not depend on a debug code or restore the removed exposure flag. Supply fixture credentials privately, never as committed defaults.

## License

MIT.
