---
title: Model - AdminUser & OfficeUser
tags: [models, users, auth]
created: 2026-08-20
---

# 👥 Model: AdminUser & OfficeUser

### 🏢 `OfficeUser` (Eloquent)
- **Table**: `office_users`
- **Fields**: `id`, `name`, `email`, `username`, `password`, `office_role` (`so`, `oso`, `sdo`, `ovcaa`), `office_title`, `is_active`.
- **Purpose**: Authenticates administrative staff logging into `/office-desk`.
- **Seed Password**: supplied privately through `OFFICE_SEED_PASSWORD`; no public default.

### 🛡️ `AdminUser` (Native PDO)
- **Table**: `admin_users`
- **Fields**: `id`, `name`, `email`, `password_hash`, `role` (`admin`, `canvassing`, `view_only`), `is_active`.
- **Purpose**: Authenticates election commissioners and canvassing officers for the voting system.
- **Login Endpoint**: `/voting-system/ssc-access-c7b4f2e91a6d`

| Role | Email | Password | Target Dashboard |
| :--- | :---- | :------- | :--------------- |
| `admin` | `admin@ssc.test` / `ssc.admin@g.batstate-u.edu.ph` | Privately provisioned | `/voting-system/admin/dashboard` |
| `canvassing` | `canvass@ssc.test` / `ssc.canvass@g.batstate-u.edu.ph` | Privately provisioned | `/voting-system/ssc-canvassing-dashboard-d8f3b72a4e91` |

