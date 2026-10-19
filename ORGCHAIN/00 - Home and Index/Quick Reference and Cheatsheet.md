---
title: Quick Reference and Cheatsheet
tags: [reference, commands, safety]
created: 2026-08-20
updated: 2026-10-16
status: active
---

# Quick Reference and Cheatsheet

Start at [[Current System Guide]]. This is not a password/account list. Test/demo identities are not guaranteed to exist, and no account has a public default password.

## Find the right guide

| Task | Reference |
| --- | --- |
| Student email-code or Google sign-in | [[Student Profile and Authentication]], [[6-Digit Email OTP Verification]] |
| Office roles and access | [[Multi-Tier Office Roles SO OSO SDO OVCAA]] |
| Exact current routes | [[Web and Portal Route Map]] |
| Local ERD files and disconnected tables | [[Database Schema and ERD]] |
| TOSA packet, Revision or evaluation | [[TOSA Application and Review Workflow]] |
| Local prerequisites and database safety | [[Local Development Setup Laragon PHP 8.3]] |
| Test isolation and mailbox-based OTP smoke | [[Testing and Quality Assurance Guide]] |
| Recorded live deployment and health signals | [[VPS Pilot Deployment and Status]] |

## Safe local commands

Run from the project root containing `artisan`, not on the VPS by accident:

```powershell
php --version
node --version
composer install
npm ci
npm run build
php -S 127.0.0.1:8000 -t public public/server-router.php
```

Use the existing project's configuration; do not overwrite `.env` or regenerate an existing `APP_KEY`. Local development commonly uses `http://127.0.0.1:8000`; a Laragon virtual host can use another base URL.

## Before database or test commands

- Confirm the selected connection names and whether configuration is cached.
- Back up existing records and originals before authorized migrations or maintenance.
- Never use a fresh/reset/seed command on a populated student or production database.
- The schema-only SQL export is for reverse engineering; do not import it over an existing live database.
- Database-backed tests need disposable schemas and private temporary storage. Some legacy helpers fall back to the project databases if isolation is omitted.
- No `EXPOSE_TEST_OTP` shortcut is supported. Obtain actual test codes from the authorized institutional mailbox.
- Do not run a blanket cache clear as a deployment ritual; invalidate only the cache affected by an authorized change.

See the detailed guides before running mutations. Native voting staff gates, Laravel office authentication and student email-code login are distinct flows; do not apply one flow's rules to another.
