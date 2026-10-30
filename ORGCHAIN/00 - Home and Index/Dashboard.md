---
title: OrgChain System Dashboard
tags: [dashboard, moc, overview, batstateu]
created: 2026-08-20
updated: 2026-10-16
status: active
---

# OrgChain Documentation Dashboard

OrgChain manages student-organization activity approvals, budgets, renewal, reports, TOSA applications and elections. This is the documentation entry point; operational records remain in the application, not the vault.

## Start here

| Need | Note |
| --- | --- |
| Understand the current system and documentation scope | [[Current System Guide]] |
| Find a subsystem or detailed reference | [[System Map MOC]] |
| Open the local ERD and understand missing connections | [[Database Schema and ERD]] |
| Follow TOSA submission, correction and manual verification | [[TOSA Application and Review Workflow]] |
| Review the recorded VPS pilot and public status boundaries | [[VPS Pilot Deployment and Status]] |
| Find safe local commands | [[Quick Reference and Cheatsheet]] |

## Reading rules

- Use current workflow notes for behavior; use dated session logs for history.
- A local database export is a snapshot, not proof that the local and deployed schemas match.
- ERD lines labeled application relationships are not database-enforced foreign keys.
- Laravel `student` and `office` guards are separate from native voting authentication.
- The configured Besu deployment uses four validators; the historical three-node JSONL/file driver is a separate mode, not automatic failover.
- Never paste passwords, keys, OTPs, database rows or private uploads into notes.

Source precedence: implementation and configuration, then current guides, then historical session notes. See [[Current System Guide]] before reproducing an old command.
