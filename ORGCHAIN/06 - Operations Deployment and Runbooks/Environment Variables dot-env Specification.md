---
title: Environment Variables (.env) Specification
tags: [operations, configuration, env, settings]
created: 2026-08-20
---

# 🔧 Environment Variables (.env) Specification

| Variable Key | Default Value | Description |
| :--- | :--- | :--- |
| `APP_NAME` | `Laravel` | Main application name |
| `APP_ENV` | `local` | Environment (`local`, `production`) |
| `APP_KEY` | `base64:...` | AES-256 encryption key |
| `APP_DEBUG` | `true` | Debug mode toggle |
| `APP_URL` | `http://localhost` | Canonical base URL |
| `OFFICE_LOGIN_PATH` | `/orgchain-office-access-a9e2f71c4b83` | Obfuscated Office Desk login URL |
| `VOTING_APP_NAME` | `OrgChain Official Voting` | Voting system header brand |
| `ADMIN_LOGIN_PATH` | `/ssc-access-c7b4f2e91a6d` | Voting admin login URL |
| `CANVASSING_DASHBOARD_PATH` | `/ssc-canvassing-dashboard-d8f3b72a4e91` | SSC Canvassing summary URL |
| `CANVASSING_TALLY_PATH` | `/ssc-canvassing-tally-73a6e2d4b8c9` | Live vote tally URL |
| `CANVASSING_REPORTS_PATH` | `/ssc-canvassing-reports-b61e7a42c9f8` | Certified reports export URL |
| `CANVASSING_REPORTS_PIN` | `""` | Optional PIN required for printing reports |
| `GOOGLE_ALLOWED_DOMAIN` | `g.batstate-u.edu.ph` | Mandatory Google OAuth domain restriction |
