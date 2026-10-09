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
| **`OfficeUserSeeder`** | Seeds the five office roles | `so.office`, `oso.office`, `sdo.office`, `ovcaa.office`, `oc.office`; requires private `OFFICE_SEED_PASSWORD` |
| **`StudentPortalSeeder`** | Seeds synthetic demo students & sample budget items | `21-00001`–`21-00003` (Demo Students One–Three), 4 initial Budget Items; requires private `STUDENT_DEMO_PASSWORD` |
| **`OrgChainUserAccountsSeeder`** | Seeds university master registry | Master records for SR-Codes `21-00001` and `21-00002` |
| **`StudentOrganizationSeeder`** | Seeds the 32 OSO-recognized orgs (AY 2025–2026) from `config/student_orgs.php` | 32 active rows in `student_organizations`; idempotent via `updateOrCreate`; **no emails seeded** |
| **`OrgSampleDataSeeder`** | Gives every recognized org sample backend data + unifies legacy org names | 1 activity + 2 budget items + fund account/sources + 2 compliance docs per org (skips orgs that already have activities); renames legacy free-text orgs and `<COLLEGE> Student Organization` fund accounts to canonical registry names |
| **`VotingStaffSeeder`** | Seeds Voting System Admin and Canvassing staff accounts | `admin@ssc.test`, `canvass@ssc.test`, plus BatStateU mirrors; requires private `VOTING_ADMIN_SEED_PASSWORD` and `VOTING_CANVASSING_SEED_PASSWORD` |

---

Seed passwords must be configured privately before running account seeders. `SystemAdminSeeder` requires `SYSTEM_ADMIN_PASSWORD`. Missing credentials abort seeding; there are no hardcoded password defaults. Real student dossier seeders and populated report examples are local-only and excluded from Git.

## ⚡ Running Seeders
For a disposable development database only. Never run `migrate:fresh` against a deployment or a database containing user records.

```powershell
php artisan migrate:fresh --seed
```
