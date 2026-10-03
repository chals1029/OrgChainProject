---
title: Database Seeders & Demo Fixtures
tags: [database, seeders, demo, fixtures]
created: 2026-08-20
---

# 🗃️ Database Seeders & Demo Fixtures

> [!tip] Quick Seeder Reference
> Seeders populate default administrative accounts, student profiles, budget allocations, and mock activities for demonstration and testing.

---

## 📋 Seeder Inventory

| Seeder Class | Primary Purpose | Default Seed Data |
| :--- | :--- | :--- |
| **`OfficeUserSeeder`** | Seeds the 4 tier office accounts | `so.office`, `oso.office`, `sdo.office`, `ovcaa.office` (Password: `Office@2026!`) |
| **`StudentPortalSeeder`** | Seeds demo students & sample budget items | `21-00001` (Charles), `21-00002` (Maria), 4 initial Budget Items |
| **`OrgChainUserAccountsSeeder`** | Seeds university master registry | Master records for SR-Codes `21-00001` and `21-00002` |
| **`StudentOrganizationSeeder`** | Seeds the 32 OSO-recognized orgs (AY 2025–2026) from `config/student_orgs.php` | 32 active rows in `student_organizations`; idempotent via `updateOrCreate`; **no emails seeded** |
| **`OrgSampleDataSeeder`** | Gives every recognized org sample backend data + unifies legacy org names | 1 activity + 2 budget items + fund account/sources + 2 compliance docs per org (skips orgs that already have activities); renames legacy free-text orgs and `<COLLEGE> Student Organization` fund accounts to canonical registry names |
| **`VotingStaffSeeder`** | Seeds Voting System Admin and Canvassing staff accounts | `admin@ssc.test` (`Admin@2026!`), `canvass@ssc.test` (`Canvass@2026!`), plus BatStateU mirrors |

---

## ⚡ Running Seeders
```powershell
php artisan migrate:fresh --seed
```
