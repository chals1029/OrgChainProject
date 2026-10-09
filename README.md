# OrgChain

OrgChain is a blockchain-backed student organization management platform for Batangas State University, The National Engineering University (BatStateU The NEU).

It links Student Organizations (SO) with the Office of Student Organizations (OSO), the Sustainable Development Office (SDO), and OVCAA in one workflow: activity proposals move through verification and approval, budgets are utilized against approved activities with receipt evidence, and reports, announcements, and archives stay in a single system of record.

## Portals and routes

### Organization / office desk (`/office-desk`, `office.auth` middleware)

| Page | Route | What it does |
| --- | --- | --- |
| Dashboard | `GET /office-desk` | Activity stats, next upcoming activity, status tracker, latest updates |
| Analytics | `GET /office-desk/analytics` | Pipeline breakdown by status, top utilized budgets |
| Activities | `GET /office-desk/activities[?activity=slug]` | Activity pipeline with for-approval / approved / in-review / returned filters |
| Create activity | `GET /office-desk/activities/create[?edit=slug]` | Guided in-campus + local off-campus submission wizard (draft or submit for review) |
| Save activity | `POST /office-desk/activities`, `PUT /office-desk/activities/{submission}` | Persists structured details, TinyMCE document HTML, and checklist uploads |
| Calendar | `GET /office-desk/calendar[?month=YYYY-MM]` | Month grid with real activity dates; clicking a date shows its activities |
| Budget Utilization | `GET /office-desk/budget-utilization` | Record expenses per approved activity, receipt attachment upload, verification queue |
| Submit receipt | `POST /office-desk/budget-utilization/receipt-reviews` | Stores reviewed receipt + expense for office review |
| Financial Report | `GET /office-desk/financial-report` | Semester expense lines sourced from recorded utilization |
| Accomplishment Report | `GET /office-desk/accomplishment-report` | End-of-term accomplishment submissions |
| Updates | `GET /office-desk/updates` | Read-only OSO announcements and downloadable template documents |
| Archive | `GET /office-desk/archive` | SO sees only its assigned organization’s folders, documents and activity files; OSO retains access across organizations |
| New folder / upload | `POST /office-desk/archive/folders`, `POST /office-desk/archive/documents` | Creates nested folders and explicitly saves uploads on the private `local` disk; DOCX content is validated and files are limited to 20 MB |
| Archive view / download | `GET /office-desk/archive/documents/{document}/view`, `GET /office-desk/archive/documents/{document}/download` | Authenticated, ownership-checked previews and original downloads; activity files use the equivalent `activity-documents` routes |
| TOSA | `GET /office-desk/tosa` | Restricted Ten Outstanding Students Awards review desk with auto-lock session |

SO archiving: open **Archive**, create a folder, choose **Upload Document**, select that folder and a DOCX file, then submit **Upload & Archive**. File selection alone does not upload or save anything. Document cards open centered previews of the actual Word content; downloads preserve the original filename and bytes. Manual archiving does not submit or change AR/FR reports.

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
- **Office Desk Auth:** Role-based access control (RBAC) supporting `SO`, `OSO`, `SDO`, `OVCAA`, and `OC` desks on obfuscated administrative paths
- **Transport Security:** Automated 256-bit TLS/SSL encryption (Let's Encrypt / Certbot)

### Production Infrastructure & Hosting
- **Target Environment:** Linux KVM Virtual Private Server (Hostinger KVM VPS, Ubuntu 22.04 LTS, 4 vCPU, 8GB RAM, 100GB NVMe SSD)
- **Containerization:** Docker & Docker Compose for background daemons and node clusters
- **Web Server:** Nginx reverse proxy with PHP-FPM 8.2+


## Local setup

```bash
composer install
npm install
```

Create `.env` and set at minimum: `DB_DATABASE`, `DB_ORGCHAIN_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `OFFICE_LOGIN_PATH`, Google OAuth (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, `GOOGLE_ALLOWED_DOMAIN`), mail credentials for login codes, and `TINYMCE_API_KEY`.

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

## License

MIT.
