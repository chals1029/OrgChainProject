---
title: Quick Reference & Cheatsheet
tags:
  - cheatsheet
  - reference
  - credentials
  - commands
created: 2026-08-20
status: active
---

# 📚 OrgChain Quick Reference & Cheatsheet

> [!tip] Developer Cheatsheet
> Keep this document pinned for rapid access to test credentials, local route endpoints, Artisan commands, and system shortcuts.

---

## 🔑 All Logins & Screen Paths

> Base URL (local): `http://127.0.0.1:8000`  
> Updated: 2026-09-18

### 1. Student portal

| Field | Value |
| :---- | :---- |
| **Login screen** | `/` (home → **Student Login** modal) or `/?login=1` |
| **How to sign in** | SR Code + 6-digit email OTP, or Google (`/student/auth/google`) |
| **After login** | `/portal` |

| Demo SR Code | Name | Email |
| :----------- | :--- | :---- |
| `21-00001` | Charles Samotanez | `21-00001@g.batstate-u.edu.ph` |
| `21-00002` | Maria Santos | `21-00002@g.batstate-u.edu.ph` |
| `23-73068` | — | `23-73068@g.batstate-u.edu.ph` |

**Student screens**
- `/portal` — Home / overview (budget utilization + **on-chain budget seals**, activities)
- `/portal/community` — Community feed

---

### 2. Office desk (SO / OSO / SDO / OVCAA)

| Field | Value |
| :---- | :---- |
| **Login screen** | `/orgchain-office-access-a9e2f71c4b83` |
| **How to sign in** | BatStateU **email** + password (not username) |
| **After login** | `/office-desk` |

| Role | Email | Password |
| :--- | :---- | :------- |
| **SO** | `so.office@g.batstate-u.edu.ph` | `Office@2026!` |
| **OSO** | `oso.office@g.batstate-u.edu.ph` | `Office@2026!` |
| **SDO** | `sdo.office@g.batstate-u.edu.ph` | `Office@2026!` |
| **OVCAA** | `ovcaa.office@g.batstate-u.edu.ph` | `Office@2026!` |

**Office screens after login**
- `/office-desk` — Dashboard
- `/office-desk/analytics` — Analytics
- `/office-desk/activities` — Activities / proposals desk
- `/office-desk/activities/create` — Create activity (SO)
- `/office-desk/calendar` — Calendar
- `/office-desk/budget-utilization` — Budget + receipt OCR + budget chain seals
- `/office-desk/financial-report` — Financial report
- `/office-desk/financial-report/print` — Printable financial report
- `/office-desk/accomplishment-report` — Accomplishment report
- `/office-desk/updates` — Updates / announcements
- `/office-desk/renewal` — **Organization Renewal** (SO submit · OSO open/close) → [[Organization Renewal Filing Window]]
- `/office-desk/archive` — Archive (OSO nav)
- `/office-desk/tosa` — TOSA module (OSO nav)

> [!note]
> Login form requires `@g.batstate-u.edu.ph` or `@batstate-u.edu.ph`. Path comes from `.env` → `OFFICE_LOGIN_PATH`.
> Renewal is **locked for SO** until OSO opens the filing window. SDO/OVCAA get **403** on `/office-desk/renewal`.

---

### 3. Voting system

| Who | Login screen | After login |
| :-- | :----------- | :---------- |
| **Voter (student)** | `/voting-system` → BatStateU Google | Ballot / vote flows under `/voting-system/...` |
| **Voting admin / staff** | `/voting-system/ssc-access-c7b4f2e91a6d` | Admin dashboard under `/voting-system/admin/...` |

**Active voting staff emails in DB** (passwords hashed — not in seed plaintext)
- `admin@ssc.test` (admin)
- `canvass@ssc.test` (canvassing)
- `canvassing@ssc.test` (canvassing)
- Plus Gmail admin accounts present in `admin_users`

**Voting screens**
- `/voting-system` — Public voting home
- `/voting-system/ssc-access-c7b4f2e91a6d` — Staff login (`ADMIN_LOGIN_PATH`)
- `/voting-system/ssc-canvassing-dashboard-d8f3b72a4e91` — Canvassing dashboard
- `/voting-system/ssc-canvassing-tally-73a6e2d4b8c9` — Live tally
- `/voting-system/ssc-canvassing-reports-b61e7a42c9f8` — Canvassing reports
- `/voting-system/admin/chain-verify` — VoteChain verification

---

## 🌐 Key URL Routing Cheatsheet

```text
Home / Student Login:     http://127.0.0.1:8000/
Student Portal:           http://127.0.0.1:8000/portal
Student Community Feed:   http://127.0.0.1:8000/portal/community
Office Secret Login:      http://127.0.0.1:8000/orgchain-office-access-a9e2f71c4b83
Office Desk Dashboard:    http://127.0.0.1:8000/office-desk
Budget Utilization:       http://127.0.0.1:8000/office-desk/budget-utilization
Renewal (SO / OSO):       http://127.0.0.1:8000/office-desk/renewal
Voting System Public:     http://127.0.0.1:8000/voting-system
Voting System Admin:      http://127.0.0.1:8000/voting-system/ssc-access-c7b4f2e91a6d
Canvassing Live Tally:    http://127.0.0.1:8000/voting-system/ssc-canvassing-tally-73a6e2d4b8c9
Canvassing Reports:       http://127.0.0.1:8000/voting-system/ssc-canvassing-reports-b61e7a42c9f8
Vote Chain Verification:  http://127.0.0.1:8000/voting-system/admin/chain-verify
```
---

## ⌨️ Essential Artisan & CLI Commands

### 🔄 Database Reset & Seeding
```powershell
# Run all migrations fresh and execute all seeders
php artisan migrate:fresh --seed

# Run individual seeders
php artisan db:seed --class=OfficeUserSeeder
php artisan db:seed --class=StudentPortalSeeder
php artisan db:seed --class=OrgChainUserAccountsSeeder
```

### ⚡ Local Development Server
```powershell
# Start local PHP server on port 8000
php -S 127.0.0.1:8000 -t public public/server-router.php

# Or using standard Laravel Artisan
php artisan serve --port=8000

# Build frontend assets
npm run build
npm run dev
```

### 🧪 Automated test DOCX report
```powershell
# ONE SHOT — backend + auth/OTP + page smoke + DOCX
npm run test:all
npm run test:all:open          # same, then open the Word report
.\scripts\run-all-tests.bat    # Windows double-click / cmd
.\scripts\run-all-tests.ps1 -Open

# Partial runs
npm run test:report            # same suite without preflight banner
npm run test:report:backend    # PHPUnit + DOCX only
npm run test:report:auth       # PHPUnit + auth/OTP + DOCX
npm run test:auth              # auth/OTP Playwright only
npm run test:smoke             # page smoke only
```
Requires `php artisan serve --port=8000` for UI/auth. Set `EXPOSE_TEST_OTP=true` for student OTP verify.
Backend PHPUnit also includes **DatabaseBackendTest** (live Laragon `votingsystem` + `orgchain` MySQL: connections, required tables, office roles, student OTP account, renewal CRUD, budget-chain dirs).
Output: `storage/app/test-reports/OrgChain-Test-Report-LATEST.docx`

### 🧹 System Cache Clearing
```powershell
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 🧪 Test Execution
```powershell
php artisan test
```

---

## 📁 Key File System Locations

| Component                 | Path                                    | Purpose                         |
| :------------------------ | :-------------------------------------- | :------------------------------ |
| **VoteChain Node 1**      | `storage/app/voting/chain/node-1/`      | JSONL Ledger for Node 1         |
| **VoteChain Node 2**      | `storage/app/voting/chain/node-2/`      | JSONL Ledger for Node 2         |
| **VoteChain Node 3**      | `storage/app/voting/chain/node-3/`      | JSONL Ledger for Node 3         |
| **BudgetChain Nodes**     | `storage/app/orgchain/budget/node-{1,2,3}/` | Budget utilization JSONL seals |
| **Renewal Uploads**       | `storage/app/public/renewal-documents/` | SO renewal packet files         |
| **Uploaded Archive Docs** | `storage/app/public/archive_documents/` | Archived compliance files       |
| **OCR Expense Receipts**  | `storage/app/public/receipts/`          | Scanned expense receipts        |
| **Smoke Report**          | `storage/app/console-smoke-report.json` | Playwright console smoke output |
| **Voting Mail Log**       | `storage/logs/voting-mail.log`          | Simulated SMTP email logs       |
| **In-Campus Templates**   | `In Campus/`                            | Official MS Word docx templates |
| **Off-Campus Templates**  | `Local Off Campus/`                     | Official CHED compliance docx   |
