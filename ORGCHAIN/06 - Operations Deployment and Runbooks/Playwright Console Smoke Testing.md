---
title: Playwright Console Smoke Testing
tags:
  - testing
  - playwright
  - qa
  - e2e
created: 2026-09-18
status: active
---

# Playwright Console Smoke Testing

> [!info] What it is
> Headless Chromium crawl that checks main screens for **console errors**, **page crashes**, and **HTTP 4xx/5xx** on documents. It is a **smoke** test, not full E2E feature QA.

## Run

```powershell
# App must be up: php artisan serve + npm run dev (or build)
node scripts/console-smoke.mjs
```

Report: `storage/app/console-smoke-report.json`  
Helpers: `scripts/mint-student-cookie.php` (OTP bypass for local smoke only)

## Coverage (full system)

| Area | What is visited |
| :--- | :--- |
| **Public** | `/`, `/voting-system`, SSC staff login path, office login path |
| **SO** | Desk pages including Renewal; probes Archive/TOSA |
| **OSO** | Full desk including Archive, TOSA, Renewal setup |
| **SDO** | Desk subset; Renewal expect **403** |
| **OVCAA** | Desk subset; Renewal expect **403** |
| **Student** | `/portal`, `/portal/community` via minted session |

Also lightly clicks a few non-submit buttons per page.

## Credentials used

See [[Quick Reference and Cheatsheet]] — office password `Office@2026!`.

## Known quirks

- `/voting-system` can **hang under Playwright** load wait while `curl` returns 200 quickly (artisan serve + voting kernel `exit`). Soft-warned in script.
- Archive/TOSA often still **render 200** for non-OSO (nav-hidden only) — harden with `abort_unless` later.
- Does **not** yet automate: activity Advance chain, renewal submit with all 10 files, OCR upload, real OTP/Google login.

## Upgrade roadmap

1. Migrate to `@playwright/test` suite (`tests/e2e/`)
2. Journey: OSO open renewal → SO draft/upload/submit
3. Journey: SO create activity → OSO Advance
4. Strict 403 matrix per role
5. Traces + HTML report on failure
6. Optional CI before pre-oral demo

## Related

- [[Testing and Quality Assurance Guide]]
- [[Organization Renewal Filing Window]]
- [[Local Development Setup Laragon PHP 8.3]]
