---
title: Obfuscated Admin & Office Route Endpoints
tags:
  - security
  - routes
  - admin
  - obfuscation
created: 2026-08-20
status: active
---

# 🕵️ Obfuscated Admin & Office Route Endpoints

> [!abstract] Defense in Depth
> Standard administrative endpoints (such as `/admin`, `/login`, `/wp-admin`) are targets for automated brute force scripts. OrgChain utilizes **Route Path Obfuscation** configurable via environment variables.

---

## 🔒 Active Obfuscated Routes

| Subsystem Area | Default Obfuscated Path | Environment Variable |
| :--- | :--- | :--- |
| **Office Desk Login** | `/orgchain-office-access-a9e2f71c4b83` | `OFFICE_LOGIN_PATH` |
| **Voting Admin Login** | `/voting-system/ssc-access-c7b4f2e91a6d` | `ADMIN_LOGIN_PATH` |
| **Canvassing Dashboard**| `/voting-system/ssc-canvassing-dashboard-d8f3b72a4e91` | `CANVASSING_DASHBOARD_PATH` |
| **Live Canvassing Tally**| `/voting-system/ssc-canvassing-tally-73a6e2d4b8c9` | `CANVASSING_TALLY_PATH` |
| **Canvassing Reports** | `/voting-system/ssc-canvassing-reports-b61e7a42c9f8` | `CANVASSING_REPORTS_PATH` |

---

## 🚨 Scanner Honey-Pot Traps

Any unauthenticated agent probing common administrative dictionary terms is automatically flagged:
- Trigger: Request to `/admin`, `/administrator`, `/manager`, `/wp-login.php`.
- Action: Recorded as `scanner_path_probe` with High severity.
- Mitigation: IP address added to the adaptive throttling rate window.
