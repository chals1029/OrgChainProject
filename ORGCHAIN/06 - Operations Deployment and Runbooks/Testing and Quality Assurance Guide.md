---
title: Testing and Quality Assurance Guide
tags: [testing, phpunit, qa, safety]
created: 2026-08-20
updated: 2026-10-16
status: active
---

# Testing and Quality Assurance Guide

Use isolated synthetic accounts, schema-only database copies and temporary private storage. A test name containing "backend" does not make it safe to run against populated Laragon or production databases.

## Database and file isolation first

1. Provision disposable copies of both legacy schemas without copying user rows. Apply the required migrations to those disposable copies through an explicitly isolated configuration.
2. Configure both `ORGCHAIN_TEST_DATABASE` and `ORGCHAIN_TEST_ORGCHAIN_DATABASE`. The current `UsesLaragonDatabase` helper reads these overrides; missing overrides can make older tests fall back to project databases.
3. Isolate normal `DB_DATABASE`/`DB_ORGCHAIN_DATABASE`, any native voting DB settings, storage and cache paths for an actual test server or migration process. Test-only override names do not automatically redirect an ordinary Artisan migration.
4. Do not load a cached production configuration. Confirm the resolved connections before any mutation.
5. Disable real email delivery and use a synthetic file-ledger configuration unless a specific authorized integration is being tested.

The announcement/community regressions refuse absent isolated-database override names. Once the isolated schemas/storage are ready:

```powershell
$env:ORGCHAIN_TEST_DATABASE = 'orgchain_test_office'
$env:ORGCHAIN_TEST_ORGCHAIN_DATABASE = 'orgchain_test_students'
php artisan test tests/Feature/AnnouncementFeedbackTest.php tests/Feature/CommunityReportIssuesTest.php
```

Those schema names are placeholders for separately provisioned test databases, not commands that create or migrate them. The local ERD snapshot is missing some current migrations; see [[Application Relationships and Schema Boundaries]].

## Browser checks

Run the real local application with the same isolated DB/storage configuration. Exercise cancellation and accepted mutation paths, role/owner boundaries, preview/download bytes, mobile overflow and console/network errors. Do not create reports, reset applications or post test comments as real users.

Existing tools include `npm run test:smoke`, `npm run test:auth` and the report scripts listed in `package.json`. Inspect their credentials/environment requirements before using them; they are not blanket production-safe commands.

OTP verification uses the actual authorized institutional test mailbox. Never enable a debug response/session/cache exposure of the code; the former `EXPOSE_TEST_OTP` instructions are obsolete. Office browser authentication requires privately supplied `E2E_OFFICE_PASSWORD`, and a selected synthetic student identity where applicable.

## Evidence and cleanup

Record the exact scenario, environment, assertions/output and any visual limitation. Passing readiness probes or a page crawl is not proof of every authenticated business workflow. After a smoke run, remove only its owned fixtures, test schemas, temporary sessions/files and servers. Do not clear unrelated production caches or reset real submissions.

The 2026-10-15 issue-report verification recorded 19 targeted passing tests plus isolated desktop/mobile workflow checks; that is historical evidence, not a test run performed by this documentation update.

See [[Playwright Console Smoke Testing]], [[Quick Reference and Cheatsheet]] and [[VPS Pilot Deployment and Status]].
