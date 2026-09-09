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
| Budget Utilization | `GET /office-desk/budget-utilization` | Record expenses per approved activity, receipt OCR scan, verification queue |
| Submit receipt | `POST /office-desk/budget-utilization/receipt-reviews` | Stores reviewed receipt + expense for office review |
| Financial Report | `GET /office-desk/financial-report` | Semester expense lines sourced from recorded utilization |
| Accomplishment Report | `GET /office-desk/accomplishment-report` | End-of-term accomplishment submissions |
| Updates | `GET /office-desk/updates` | Read-only OSO announcements and downloadable template documents |
| Archive | `GET /office-desk/archive` | Persistent archive folders and documents |
| New folder / upload | `POST /office-desk/archive/folders`, `POST /office-desk/archive/documents` | Creates folders; stores uploads under `storage/app/public/archive` |
| TOSA | `GET /office-desk/tosa` | Restricted Ten Outstanding Students Awards review desk with auto-lock session |

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
- **Offices** (`office` guard, `App\Models\OfficeUser` with roles `so`, `oso`, `sdo`, `ovcaa`): BatStateU email + password on the private login path configured by `OFFICE_LOGIN_PATH`.

## Key data

- Default `mysql` connection (`DB_DATABASE`, voting data): `office_users`, `org_activities`, `in_campus_activity_submissions` (in-campus + `local_off_campus` types, TinyMCE HTML columns, JSON attachments), `expense_receipt_reviews`, `archive_folders`, `archive_documents`, `budget_items`.
- `orgchain` connection (`DB_ORGCHAIN_DATABASE`): `user_accounts` (students by `sr_code`), `community_posts`, `community_comments`, `community_likes`.
- Uploads live under `storage/app/public` (`in-campus-activities/`, `expense-receipts/`, `archive/`); run `php artisan storage:link` to expose them.

## Tech stack

- [Laravel 13](https://laravel.com), PHP 8.3, Blade
- [Vite](https://vite.dev) + [Tailwind CSS](https://tailwindcss.com), Bootstrap Icons
- [TinyMCE 7](https://www.tiny.cloud) (key via `TINYMCE_API_KEY`) for Programme, Project Proposal, Budget Proposal, Faculty-in-Charge, and off-campus letters
- [Tesseract.js](https://tesseract.projectnaptha.com) (CDN, runs locally in the browser) for receipt OCR pre-fill on the Budget Utilization page

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
