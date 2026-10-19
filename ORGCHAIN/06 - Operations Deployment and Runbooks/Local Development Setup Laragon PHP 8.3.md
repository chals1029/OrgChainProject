---
title: Local Development Setup (Laragon and PHP 8.3)
tags: [operations, setup, laragon, php, development]
created: 2026-08-20
updated: 2026-10-16
status: active
---

# Local Development Setup

This guide prepares the existing Windows/Laragon project. It does not reset data, migrate the VPS or change deployed services.

## Prerequisites

- PHP matching `composer.json` (`^8.3`) with the extensions required by Composer, MySQL, file/MIME handling, archives and enabled document features.
- The project's two MySQL schemas: primary office/voting data and `orgchain` student/community data. The Laravel migrations alone are not proof that every native legacy table can be recreated from an empty database.
- Composer 2 and Node compatible with Vite 8's declared engine requirements; use the manifests/lockfiles, not the old Node 18 assumption.
- Docker and the supplied converter/network configuration only when exercising those services.

## Preserve existing configuration

Obtain an authorized local `.env` and schema-only database structure privately. Configure `DB_DATABASE` and `DB_ORGCHAIN_DATABASE` for the intended local databases. Never copy production rows or credentials into the vault, and never replace an existing application key merely to start a server.

Before applying migrations, confirm the actual selected database and cached-config state. Back up records/originals; review and run only the required non-destructive migrations on the authorized environment. Do not run a fresh/reset/seed command on the populated project databases.

## Install and start

From the repository root:

```powershell
composer install
npm ci
npm run build
php -S 127.0.0.1:8000 -t public public/server-router.php
```

Keep the server bound to loopback for local work. The supplied router restricts static storage access; do not replace it with an unrestricted file server. Laragon can instead serve the project's `public/` directory through its configured web server.

`APP_URL` must match the selected local base URL. Only use mailboxes, external providers and blockchain nodes you are authorized to exercise; isolated tests should use their test mail/ledger configuration.

[[Environment Variables dot-env Specification]] documents variable names without secrets. [[Testing and Quality Assurance Guide]] covers isolated schema/storage prerequisites. [[Database Schema and ERD]] links the existing local schema-only snapshot; it is not a migration script or a full platform backup.
