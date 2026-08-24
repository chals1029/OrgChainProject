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

### 🛡️ `AdminUser` (Native PDO)
- **Table**: `admin_users`
- **Fields**: `id`, `name`, `email`, `password_hash`, `role` (`admin`, `canvassing`, `view_only`), `is_active`.
- **Purpose**: Authenticates election commissioners and canvassing officers for the voting system.
