---
title: Current System Guide
tags: [current-state, overview, documentation]
created: 2026-10-16
updated: 2026-10-16
status: active
---

# Current System Guide

This note separates current implementation, local schema snapshots and deployment history. Reviewed against repository code, the local ERD exports, `README.md` and `CHANGELOG.md`; this documentation update did not access or change the website.

## Current implementation

| Area | Current behavior | Detail |
| --- | --- | --- |
| Identity | Students use `orgchain.user_accounts`; office accounts have SO, OSO, SDO, OVCAA or OC roles. Adviser/dean names do not create separate login roles. | [[Student Profile and Authentication]], [[Multi-Tier Office Roles SO OSO SDO OVCAA]] |
| Activities | SO submits; OSO, SDO, OVCAA and OC review in sequence. Student-visible activities require final OC approval. | [[Activity Proposal Approval Pipeline]] |
| Budgets | Approved activities determine allocations; actual expenses and preserved earlier spending are not replaced by template/example numbers. | [[Budget Utilization and OCR Receipts]] |
| Community | Actual counts, seven persisted reactions, audience-scoped posts/photos, server-side search and expiring success messages. | [[Community Feed Posts and Likes]] |
| Announcements and feedback | Published notices feed the student surfaces; office notice acknowledgment is persisted, while OSO feedback acknowledgment notifies only its submitting student. | [[Student Profile and Authentication]], [[Office Portal API and Action Endpoints]] |
| TOSA | OSO announces, reviews seven-document applications and manually publishes an unranked Top 10; the desk records new activity in History. Returned packets can be corrected. | [[TOSA Application and Review Workflow]] |
| AR / FR | Separate report submissions, review decisions and filing locks. | [[Semester AR and FR Submission Workflow]] |
| Documents | Private originals and authorized view/download routes; reference files are not submitted evidence. | [[Document Archive and Compliance Repository]] |

## Database scope

The local physical export contains 64 tables, 668 columns and 47 declared foreign-key constraints across `votingsystem` and `orgchain`. Read the export header for its extraction timestamp. Application relationships may exist without SQL constraints, especially across schemas.

The office announcement acknowledgment migration was applied in the last recorded VPS release but its table is absent from that local snapshot. Newer TOSA review columns may also differ. Do not add fictional columns or tables to the physical export. See [[Database Schema and ERD]] and [[Application Relationships and Schema Boundaries]].

## Deployment scope

The last recorded application release is `f260201` (2026-10-15). Its record describes an Ubuntu VPS, Nginx/PHP-FPM, MySQL, a four-validator Besu network and a Cloudflare Tunnel. This is a deployment record, not a new live-health observation. [[VPS Pilot Deployment and Status]] explains the boundaries.

Four validators on one VPS do not provide four independent host failure domains. The file/JSONL driver remains a separate configured mode; never describe a failed Besu operation as a successful fallback transaction.

## Keeping the vault current

1. Change implementation first; verify the affected behavior.
2. Update its existing subsystem/workflow note and route/model reference where needed.
3. Update [[Database Schema and ERD]] only from an identified schema snapshot or verified code relationship.
4. Update [[System Map MOC]] for new or renamed notes.
5. Record date, source files and local-versus-deployed scope. Preserve historical session logs.
6. Keep credentials, records and uploaded evidence out of documentation and attachments.

Repository source anchors: `routes/web.php`, `routes/health.php`, `app/Models/`, `app/Http/Controllers/`, `app/Services/TosaRequirements.php`, `config/`, `infra/vps/`, `composer.json`, `package.json` and the project README/release notes.
