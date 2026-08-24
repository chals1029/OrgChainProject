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

---

## ⚡ Running Seeders
```powershell
php artisan migrate:fresh --seed
```
