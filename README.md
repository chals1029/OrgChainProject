# OrgChain

OrgChain is a blockchain-backed student organization management platform for Batangas State University, The National Engineering University (BatStateU The NEU).

It links Student Organizations (SO) with the Office of Student Organizations (OSO), the Sustainable Development Office (SDO), and OVCAA in one workflow: activity proposals move through verification and approval, budgets are utilized against approved activities with receipt evidence, and reports, announcements, and archives stay in a single system of record.

## Portals and routes

### Organization / office desk (`/office-desk`, `office.auth` middleware)

| Page | Route | What it does |
| --- | --- | --- |
| Dashboard | `GET /office-desk` | Activity stats, upcoming activity and workflow tracker; Recent Updates contains posted announcements only and View All opens Updates |
| Analytics | `GET /office-desk/analytics` | Pipeline breakdown by status, top utilized budgets |
| Activities / Proposals | `GET /office-desk/activities[?activity=slug]` | SO activity management; OSO/SDO/OVCAA/OC proposal review with for-approval / approved / in-review / returned filters |
| Create activity | `GET /office-desk/activities/create[?edit=slug]` | Select an activity from the assigned organization’s approved Attachment I and prepare the in-campus/off-campus checklist |
| Submit activity | `POST /office-desk/activities`, `PUT /office-desk/activities/{submission}` | Derives title/reference from the selected approved-plan entry and submits structured details plus all required uploads together; ₱0 budgets are allowed |
| Calendar | `GET /office-desk/calendar[?month=YYYY-MM]` | Month grid with real activity dates; clicking a date shows its activities |
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

SO archiving: open **Archive**, create a folder, choose **Upload Document**, select that folder and a DOCX file, then submit **Upload & Archive**. File selection alone does not upload or save anything. Document cards open centered previews of the actual Word content; downloads preserve the original filename and bytes. Manual archiving does not submit or change AR/FR reports.

OSO Archive: search an organization, filter by academic year or college, then choose **Open archive**. Groups show preserved **AR & FR reports**, approved **Renewal records**, **Activity documents**, and **Other preserved files**. Nested files are included, not just the recent-file list. Each document identifies its original filename and source folder/activity or renewal period; **View** uses authenticated original-file access. **Folders & preserved files** retains the existing folder browser. The shared preview shell has **Close / Open in Tab / Download original**; DOCX rendering and the native PDF viewer may differ internally, while original downloads remain unchanged.

SO Budget Utilization: summary cards and charts precede **Record Expense**. Select an approved activity, attach up to three shared receipts, enter one or more item cards, then choose **Record expense** once. **Add item / Remove / Clear** manage the local batch; server validation, exact quantity × unit-cost totals, one-time cash posting and receipt seals remain authoritative. OSO organization search filters the five-row ledger directory without changing consolidated cash totals.

SO Accomplishment Report: **Create activity report** uploads a completed **DOC / DOCX / PDF** (up to 20 MB) for the selected academic year and semester. Upload saves a private draft; **Submit AR to OSO** is separate and immediately locks the submitted report. Only a returned-for-revision report can be changed afterward. Existing native activity reports remain editable where permitted; their generated packets retain evidence/completeness checks, while independent uploaded completed reports are not blocked by unrelated native drafts. AR/FR period selectors refresh automatically. OSO **View Submitted Reports** is available when either report has a readable submitted file and opens the available type.

SO Financial Report shows **Current Reports** followed by **Financial Report History**. Empty current reports offer **Create report**; history search/year/semester filters remain usable when empty. Creating new Financial Reports still requires the completed official XLSX workbook and an exact-file preview before submission. Viewing unsupported legacy Word files does not satisfy that submission gate.

SO Renewal: populated adviser/dean names start read-only. Use **Edit information → Save changes** only when they change. This saves names without reloading or discarding selected files; a names-only draft is not visible in OSO’s submitted packets. Valid local selections and actual saved files update the green checklist/readiness count. **Submit for Review** uploads the complete packet once and locks editing immediately. OSO **Return** or **Reject** reopens that organization’s packet while the filing window is open and the organization remains eligible; **Approved** stays final.

OSO Budget Utilization: **View Details** in the organization directory opens an organization-only activity dialog with the current reporting period, total approved budgets and total encoded expenses. Selecting an activity stays inside the popup and shows its scope, recorded payment methods, approved budget, actual spending, signed remaining balance, utilization, category breakdown, same-scale budget comparison and searchable expense items. **Back to activities** restores the list and focus. Year, semester, department and organization scope are preserved. **View** opens the existing private, read-only receipt viewer over the details; multi-file navigation and unchanged-original downloads remain available, and closing the receipt returns to the selected activity.

Organization **encoded expense** totals sum eligible receipt item quantity × unit cost, including items awaiting a seal and excluding rejected/unposted items. Selected-activity **actual expenses** also retain earlier recorded spending; any difference appears as a separate chart category and explanatory note, never fabricated receipt rows. Zero approved budget displays **N/A** utilization, and overspending keeps its negative balance and percentage above 100% while only the visual progress bar saturates. Search does not change totals; receipt statuses and payment methods come from stored records. Cash balances and approval records remain unchanged. Close details, ×, Escape and backdrop dismissal return to the directory; short screens scroll content and the expense table while keeping close controls visible.

Activity proposals select actual titles from the current approved renewal **Attachment I**, not from the blank template or separately typed titles/references. Choices are bound to the organization, approved source and file version; changed plans require reselection. Completed text-readable DOCX, PDF and legacy DOC plans supply choices; blank templates and image-only scans do not. **₱0** activities can complete final approval without an opening cash account or a cash reservation. Selected/saved filenames preview their actual bytes. Authorized office DOC previews convert privately, while Download original retains the DOC filename and bytes. Approved-plan viewers open the actual approved dossier file, with **Close** beside **Download original**.

The official **Waste Policy Compliance Form (WPCF)** is now supplied as `storage/Template/Creation Of Activties/In Campus Activities/Waste-Policy-Compliance-Form-2026 (1).docx`, genuinely converted from the supplied Word 97–2003 original. The catalogue uses this packaged source directly, not the ignored local `In Campus` junction. Creation-form downloads, the in-campus template ZIP and Updates use the modern editable DOCX. Official previews use the packaged PDF rendered from that DOCX so the university logo, floating graphics, form lines and checkboxes remain faithful without a runtime Docker conversion. The original DOC is retained locally for provenance, but is no longer an active template selector. Existing submitted files, their original bytes and review history are not rewritten.

Activity-creation document previews use a viewport-height dialog with a full-height flex container and a remaining-space document pane. Desktop previews use up to 90% of the viewport height; mobile previews leave a small screen gutter. Header and Close/Download controls stay visible while the document scrolls, including short landscape viewports. Percentage-height PDF frames no longer depend on an auto-height parent or a fixed minimum height.

SDO, OVCAA and OC use the same **Proposals** navigation label and icon as OSO, with role-specific review instructions and approval actions retained. Submitted document badges show the latest passed stage: **OSO approved** while awaiting SDO, **SDO approved** while awaiting OVCAA, **OVCAA approved** while awaiting OC, and **OC approved** after final approval. Returned/rejected states take precedence; display changes never write approvals. Private DOC, DOCX and PDF previews use actual submitted bytes, with **Close** immediately beside **Download original**. The SDO review checklist appears below **Submitted Compliance Documents**; its required acknowledgement belongs to the original **Endorse to OVCAA** form, and original stage/organization access gates are unchanged.

OSO **AR & FR Reports → Submission controls** locks AR and FR independently for the selected academic year and semester. **Lock AR/FR submissions** first opens a report-specific confirmation showing the period and **all-organizations** scope. Only **Yes, lock submissions** posts the selected lock; Cancel, Escape and backdrop dismissal leave server state untouched. The dialog is immediately visible, keyboard-safe and responsive, without inheriting page entrance animations. Existing submitted reports remain reviewable and SO can still prepare, preview and download while locked. **Reopen** keeps its direct behavior and does not change the other report type or neighboring periods.

TOSA opens on **Applicants** with search, review-stage/program/year filters, pagination and a filtered roster export. **Requirements** separates the applicant’s A–G supporting-credential checklist from five official reference/template downloads. **Top10** is an academic-year-scoped shortlist ordered by finalized, verified total points, not GWA alone or an automatic award decision. Dossiers retain private original downloads, PDF previews and persisted stage, remarks and GWA review. Drafts and never-submitted applications are excluded from office lists, counts, exports and direct document/review access. Student submission requires all seven readable, nonempty, correctly formatted, owner-bound private packet sections—not merely upload metadata. Previously submitted records remain visible if an original later becomes unavailable; opening that file reports an error. No missing-document counters or fabricated TOSA dashboard/audit data are shown.

The five official **references/templates** are **Front Page.doc**, **Guidelines TOSA 2025.pdf**, **TOSA Application Form 2025.docx**, **TOSA COMPUTATION.xlsx**, and **TOSA Submission Guide.doc**. They are not five mandatory student uploads. The guide’s **“Nomination Application Form” is the same TOSA Application Form 2025.docx**, not an additional form: download it, complete it with the required photo/signatures, and submit the completed version in section B. The packet order is **A** labeled cover/table of contents, **B** completed TOSA Application Form, **C** profile/résumé/CV, **D** good moral certificate, **E** academic evidence, **F** leadership evidence, and **G** social-responsibility evidence. Academic evidence includes Registrar-certified grades with GWA and evidence for claimed awards/participation; leadership includes positions/awards and certified labeled supporting copies; social responsibility separates school and non-school activity evidence.

The portal’s digital uploads accept **PDF** for supporting sections and **PDF or DOCX** for the completed nomination, up to **25 MB per file**. These are platform upload formats, not a claim that the paper guide requires PDFs. Choose or replace files, preview them, then click **Submit for Review once** to save the whole packet and send it to OSO. File selection alone does not save or submit anything. Guides, the blank nomination and the computation workbook are references, not supporting credentials. File-format/readiness checks do not verify signatures, extract GWA or certify achievements; scoring uses evaluator-entered and verified evidence, not automatic document interpretation. No filing deadline is inferred from the guide’s inconsistent 2024/2025 dates.

Student TOSA uses compact, keyboard-accessible collapsible sections with one maroon accent: Submissions, application status, preparation guides, A–G supporting credentials and submitted lock information. The student page does not display the personal score/calculation section or precompute its ranking data; evaluator scoring, qualification ranking and workbook endpoints remain unchanged. The official application-form card and preparation library start expanded; other sections start collapsed, while Revision opens status/remarks and credentials automatically. Submission errors and native file validation reveal the credentials section. Reference cards show only their title and consistent **View / Download** controls, use two columns on larger screens and stack on narrow phones. PDF and DOCX templates, saved originals and local selections open the same centered, responsive viewer used by Renewal; clicking a filename opens its preview, and original downloads retain their names and bytes. Word pages keep their original width with scrolling on small screens. **Open in Tab** is available for PDFs/images, not native Word downloads. **Front Page** and **TOSA Submission Guide** DOC references use the existing isolated Docker converter for temporary, private PDF previews of the currently resolved official file, including a valid OSO-uploaded replacement. Generated workspaces are released after streaming; conversion failure reports an error without changing the source or blocking its original DOC download. Students cannot upload arbitrary files to the office conversion endpoint. The computation XLSX opens the existing private, read-only worksheet viewer. Selecting files does not save them individually: the single **Submit for Review** saves and submits the complete packet.

TOSA has no per-file Save/Delete buttons or mutation endpoints. Selected files stay local and show **Selected · Not submitted** until the single multipart `POST /portal/tosa/submit`. Choosing a file hides its unsaved **Not uploaded** or **File unavailable** block; cancelling restores that state. A replacement selection keeps the actual saved original visible and downloadable until submission. Each submission requires all A–G sections as valid selections or actual saved private originals; invalid choices must be corrected or cancelled. Cancellation clears only that local selection, and saved-file downloads stay bound to saved bytes. Validation, storage or database failure preserves the previous packet and review and removes newly written files. **Successful submission immediately locks the application. Only Revision unlocks editing and resubmission:** the review desk displays the existing persisted `subsection=returned` stage as **Revision**. Students see reviewer remarks, replace the affected files and click **Resubmit for Review**; unchanged originals carry over. Resubmission returns the packet to **Screening** and locks it again, clearing stale GWA verification, review remarks and score evaluation when evidence changes. Screening, interview, rejected and accepted applications remain read-only; accepted records cannot bypass the lock through conflicting revision metadata. The server rechecks the persisted stage, including requests from stale revision pages. View and Download remain available in every stage. No applicant data reset is performed. Authorized OSO replacement references use `POST /office-desk/tosa/references/template` with `reference_key` and `template_file`; source-ID downloads retain bundled originals and record-ID downloads retain the requested upload. Historical metadata and bytes remain preserved without reclassifying guides/workbooks as evidence or old grades/certificates as complete bundles.

**Evaluator scoring:** authorized OSO/OVCAA evaluators enter GWA and evidence-referenced academic awards, leadership seminars/positions/awards and school/non-school social activities. The server computes **100 points maximum**: grades 30, academic awards 10, leadership 30 and social responsibility 30, with each criterion’s category caps and exact integer milli-points. **Save provisional** exposes a draft calculation only. **Finalize verified score** requires verified GWA from 1.00 to 3.00, verified eligibility, a consecutive academic year and all seven original credentials. Scoring does not accept the application or award TOSA automatically.

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

### Voting system (`/voting-system`)

Integrated official voting module (admin, voter flows, Google OAuth, canvassing) served through `App\VotingSystem\Kernel` under the `/voting-system` prefix.

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

- Default `mysql` connection (`DB_DATABASE`, voting data): `office_users`, `org_activities`, `in_campus_activity_submissions` (in-campus + `local_off_campus` types, TinyMCE HTML columns, JSON attachments), `expense_receipt_reviews`, `archive_folders`, `archive_documents`, `budget_items`.
- `orgchain` connection (`DB_ORGCHAIN_DATABASE`): `user_accounts` (students by `sr_code`), `community_posts`, `community_comments`, `community_likes`.
- Activity and receipt uploads, plus existing report archives, retain their public storage paths under `storage/app/public`; `php artisan storage:link` exposes that disk. New manual archive uploads use the private `local` disk instead. `archive_documents.file_disk` preserves legacy `public` records while authenticated archive view/download routes enforce organization ownership.

## Source control and private configuration

Keep `.env`, credentials, signing keys, database dumps, uploaded records and populated report examples out of Git. The local real-student TOSA seeder, supplied accomplishment reference PDF and populated CARS renewal examples are excluded; blank official templates remain available. Untracking a file preserves its local copy but does not erase older Git history. Previously published credentials require coordinated rotation, and historical private records require a separate history-cleanup plan.

Account seeders have no public password defaults. Configure `OFFICE_SEED_PASSWORD`, `STUDENT_DEMO_PASSWORD`, `VOTING_ADMIN_SEED_PASSWORD`, `VOTING_CANVASSING_SEED_PASSWORD` and `SYSTEM_ADMIN_PASSWORD` privately before running the corresponding seeders. Demo student and registry identities are synthetic. Never run destructive development database resets against user data.

Configure `BLOCKCHAIN_NODE_SECRET` privately and consistently across the participating nodes. The slim node also accepts `ORGCHAIN_NODE_SECRET`; it refuses startup without a secret, and the receive API rejects missing server configuration or mismatched tokens. The PowerShell launcher requests a private secret without echoing it or saving it in its connection handout. Office smoke scripts require private `E2E_OFFICE_PASSWORD` configuration.

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

Create `.env` and set at minimum: `DB_DATABASE`, `DB_ORGCHAIN_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `OFFICE_LOGIN_PATH`, Google OAuth (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, `GOOGLE_ALLOWED_DOMAIN`), mail credentials for login codes, and `TINYMCE_API_KEY`.

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

Upptime checks the public landing page (HTTP 200 and OrgChain content) and Laravel's existing `/up` endpoint (HTTP 200). These checks measure public availability, not successful sign-in, institutional email delivery, database readiness or private document submission. Monitoring history starts on 15 October 2026; percentages reflect observations since monitoring began, not historical measurements from before deployment.

The `OrgChain Status` workflow schedules availability checks and publication every five minutes; GitHub schedules can be delayed. It publishes a custom static interface and same-origin `status.json`, so visitors do not need GitHub API access. The two-column service grid provides a 90-day UTC timeline, daily details, maintenance notices and incident history without a provider footer. Days before monitoring began remain gray; partial-day uptime uses only observed minutes, and overlapping outage intervals are counted once. Checks or snapshots older than 15 minutes, future dates and failed refreshes are explicitly delayed/unavailable, not falsely operational. Response-time recording remains daily or on manual/configuration-triggered runs. If results appear stale, inspect that repository's Actions runs. No VPS task or additional VPS is needed.

Monitoring uses only the repository-scoped, short-lived `GITHUB_TOKEN`, with separate permissions for monitoring, site building and Pages deployment. No application credentials, SMTP/OAuth secrets, private documents, student records or broad personal access token are stored in the public status repository. Cloudflare DNS has a DNS-only CNAME from `status` to `chals1029.github.io`; GitHub Pages enforces HTTPS.

Detected outages open `status` issues and recovery closes them; short incidents are retained. Publish planned maintenance through an issue labeled `maintenance`, using Upptime's UTC `start`/`end` comment metadata and `expectedDown`/`expectedDegraded` component slugs (`website`, `application-health`) where appropriate. Keep incident text public-safe; never paste raw application logs or student information.



### Safe regression and browser checks

For disposable MySQL schemas, set `ORGCHAIN_TEST_DATABASE` and `ORGCHAIN_TEST_ORGCHAIN_DATABASE`; the existing Laragon test helper otherwise uses the configured databases. Use schema-only clones, not copies of real student rows. Override `LARAVEL_STORAGE_PATH` and `APP_CONFIG_CACHE` to temporary paths, copy required official `storage/Template` sources into that isolated storage root, and set `BLOCKCHAIN_DRIVER=file` explicitly for file-ledger checks. Never run destructive storage tests against live uploads.

`php artisan test --compact` runs the backend suite. `npm run build` builds browser assets. The interactive `scripts/auth-otp-smoke.mjs` accepts only a code actually delivered to the institutional mailbox; it must not depend on a debug code or restore the removed exposure flag. Supply fixture credentials privately, never as committed defaults.

## License

MIT.
