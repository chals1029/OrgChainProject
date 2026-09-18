---
title: Testing & Quality Assurance Guide
tags: [testing, phpunit, qa, quality]
created: 2026-08-20
---

# 🧪 Testing & Quality Assurance Guide

> [!info] Test Automation
> OrgChain uses **PHPUnit** for backend unit/feature tests and **Playwright** for full-system browser smoke (console + HTTP).

---

## Auth & OTP testing

### Backend (full OTP logic)
```powershell
php artisan test --filter=AuthAndOtpTest
```
Covers: office domain/password login + logout, student OTP send/verify, wrong code, 5-attempt lockout, portal guest redirect.

### Database backend (live Laragon MySQL)
```powershell
php artisan test --filter=DatabaseBackendTest
```
Hits real `.env` DBs (`votingsystem` + `orgchain`): connections, required tables, office desk roles, active student for OTP, renewal window read/write, budget-chain storage dirs. Auth/Renewal Feature tests use the same Laragon DB helper (no skips).

### Browser auth journey
```powershell
# Optional for full OTP *verify* in browser (local only):
# EXPOSE_TEST_OTP=true in .env, then restart `php artisan serve`
npm run test:auth
```

### One-shot full suite → DOCX
```powershell
npm run test:all
```

### Combined DOCX (backend + auth UI, skip long page crawl)
```powershell
npm run test:report:auth
```

### Upgrade path
1. `@playwright/test` suite under `tests/e2e/`
2. Renewal journey (OSO open → SO submit)
3. Activity Advance chain
4. Strict 403 matrix + traces/HTML report

---

## 🏃 PHPUnit

```powershell
# Run all tests
php artisan test

# Run a specific test class
php artisan test --filter=DatabaseBackendTest
php artisan test --filter=AuthAndOtpTest
```

### Key areas covered by PHPUnit
1. **DatabaseBackendTest** — live mysql/orgchain connectivity + schema + seed sanity
2. **AuthAndOtpTest** — office login + student OTP
3. **RenewalAccessTest** — SO/OSO/SDO renewal gates
4. **BudgetChainService** — 3-node seal + hash links
5. **Multi-tier office authentication**