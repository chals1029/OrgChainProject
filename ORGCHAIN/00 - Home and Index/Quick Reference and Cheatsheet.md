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

## 🔑 Default Test Accounts & Credentials

| Role | Username / Identifier | Password | Access URL |
| :--- | :--- | :--- | :--- |
| **SO Officer** | `so.office` | `Office@2026!` | `/orgchain-office-access-a9e2f71c4b83` |
| **OSO Officer** | `oso.office` | `Office@2026!` | `/orgchain-office-access-a9e2f71c4b83` |
| **SDO Officer** | `sdo.office` | `Office@2026!` | `/orgchain-office-access-a9e2f71c4b83` |
| **OVCAA Officer** | `ovcaa.office` | `Office@2026!` | `/orgchain-office-access-a9e2f71c4b83` |
| **Student 1** | `21-00001` (Charles) | `Student@2026!` / OTP | `/` (Home login modal) |
| **Student 2** | `21-00002` (Maria) | `Student@2026!` / OTP | `/` (Home login modal) |
| **Voting Admin** | `admin@batstate-u.edu.ph` | Configured in DB | `/voting-system/ssc-access-c7b4f2e91a6d` |
| **Canvassing Staff**| `canvassing@batstate-u.edu.ph`| Configured in DB | `/voting-system/ssc-canvassing-dashboard-d8f3b72a4e91` |

---

## 🌐 Key URL Routing Cheatsheet

```text
Student Portal:           http://127.0.0.1:8000/portal
Student Community Feed:   http://127.0.0.1:8000/portal/community
Office Desk Dashboard:    http://127.0.0.1:8000/office-desk
Office Secret Login:      http://127.0.0.1:8000/orgchain-office-access-a9e2f71c4b83
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

| Component | Path | Purpose |
| :--- | :--- | :--- |
| **VoteChain Node 1** | `storage/app/voting/chain/node-1/` | JSONL Ledger for Node 1 |
| **VoteChain Node 2** | `storage/app/voting/chain/node-2/` | JSONL Ledger for Node 2 |
| **VoteChain Node 3** | `storage/app/voting/chain/node-3/` | JSONL Ledger for Node 3 |
| **Uploaded Archive Docs** | `storage/app/public/archive_documents/` | Archived compliance files |
| **OCR Expense Receipts** | `storage/app/public/receipts/` | Scanned expense receipts |
| **Voting Mail Log** | `storage/logs/voting-mail.log` | Simulated SMTP email logs |
| **In-Campus Templates** | `In Campus/` | Official MS Word docx templates |
| **Off-Campus Templates** | `Local Off Campus/` | Official CHED compliance docx |
