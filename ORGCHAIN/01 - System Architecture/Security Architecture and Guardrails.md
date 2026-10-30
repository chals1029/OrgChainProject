---
title: Security Architecture and Guardrails
tags: [security, authorization, private-storage, ratelimiting]
created: 2026-08-20
updated: 2026-10-16
status: active
---

# Security Architecture and Guardrails

Security depends on server-side identity, authorization, validated input, private storage and transport controls. Hidden login paths, disabled buttons, confirmation dialogs and blockchain hashes are not substitutes for these controls.

## Portal boundaries

- Student identity uses `UserAccount` on the `orgchain` connection; inactive accounts cannot use the student portal.
- Office accounts use a separate guard. Active SO accounts require an active assigned organization. Roles and workflow stages constrain reads and writes.
- Office credential/account changes revoke stale sessions through the current `auth_version` state. Temporary credentials must be replaced before normal access.
- TOSA has no PIN gateway. Active OSO can verify documents; authorized OVCAA access is read-only. Submitted packets lock unless returned for correction.
- Student feedback is OSO-reviewed; acknowledgment notifications/history are scoped to the owner, including anonymous submissions.
- Community visibility and photo access enforce the selected public-campus, college or organization audience. Direct community-storage paths are denied.

## Originals and configuration

Confidential files use authorized view/download routes. Legacy disk metadata does not grant anonymous static access; Nginx, Apache and the development router restrict storage prefixes. Local file selection is not an uploaded/saved record. Conversion failure cannot replace an original with a fake preview.

Keep `.env`, signing keys, tunnel credentials, SMTP secrets, database dumps with records and private uploads out of Git and this vault. Only schema-only ERD attachments belong in [[Database Schema and ERD]]. Reusing an existing `APP_KEY` matters for encrypted data and sessions; do not regenerate it as a maintenance shortcut.

## Native voting security

`App\VotingSystem\Core\SecurityGuard` runs in the voting kernel. Its configurable defaults are not universal rate limits for every Laravel endpoint:

| Pool | Default window | Default maximum |
| --- | --- | --- |
| Global voting request | 60 seconds | 1,200 |
| Public voter request | 60 seconds | 600 |
| Staff voting path | 300 seconds | 120 |
| Repeated SQL-injection signals | 3,600 seconds | 5 signals before the hard block |

These values come from `config/voting.php` and `SecurityGuard.php`; deployed environment overrides can change the configurable pools. Pattern detection is an additional signal, not a replacement for bound SQL and authorization. Portal login has its own named throttles. See [[6-Digit Email OTP Verification]] and [[Session Management and Lifetime Policies]].

## Transport and public health

The recorded pilot exposes HTTPS through Cloudflare Tunnel to a loopback origin. RPC/node credentials and private diagnostics are not public status fields. Public readiness endpoints expose cached allowlisted states/timestamps; scheduled private probes do not submit votes, send user email, read student evidence or run migrations. A green readiness signal does not prove every authenticated workflow.

Business confirmations help prevent accidental changes but do not authorize a forged request. Read-only actions should not mutate approval state. See [[VPS Pilot Deployment and Status]] and [[Testing and Quality Assurance Guide]].

Source: `EnsureStudentAuthenticated`, `EnsureOfficeAuthenticated`, `StudentAuthController`, `CommunityFeedController`, `routes/health.php`, `config/voting.php`, `SecurityGuard.php`, supplied web-server rules and current project README.
