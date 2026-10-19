---
title: Environment Variables (.env) Specification
tags: [operations, configuration, env, security]
created: 2026-08-20
updated: 2026-10-16
status: active
---

# Environment Variables Reference

Names and source locations only. Keep actual values in private environment/configuration files; never paste a live `.env` into the vault. Repository defaults and installed production values can differ.

| Group | Relevant names | Source and caution |
| --- | --- | --- |
| Application | `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_KEY` | `config/app.php`; production debug must be off. Preserve an existing key. |
| Databases | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_ORGCHAIN_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | `config/database.php`; two MySQL schema connections. |
| Native voting DB | `VOTING_DB_DRIVER`, `VOTING_DB_HOST`, `VOTING_DB_PORT`, `VOTING_DB_DATABASE`, `VOTING_DB_USERNAME`, `VOTING_DB_PASSWORD` | `config/voting.php`; inspect effective selection, not just the primary connection. |
| Sessions | `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE` | `config/session.php`; Laravel settings are not the native voting idle/absolute policy. |
| Institutional sign-in/mail | Project Google OAuth and `MAIL_*` settings | `config/services.php`, `config/mail.php` and auth controllers; client secrets and SMTP passwords are private. |
| Staff paths/gates | `OFFICE_LOGIN_PATH`, `ADMIN_LOGIN_PATH`, `CANVASSING_*` path/PIN settings | Configured routing does not replace authentication/authorization. |
| Besu | `BLOCKCHAIN_DRIVER`, `BESU_RPC_URL`, `BESU_CHAIN_ID`, `BESU_CONTRACT_ADDRESS`, `BESU_VALIDATOR_COUNT` | `config/besu.php`; the file driver is the configuration default, not proof of the recorded pilot's selected mode. |
| Signing/node authentication | `BESU_SIGNER_PRIVATE_KEY`, `BESU_SIGNER_PRIVATE_KEY_FILE`, `BLOCKCHAIN_NODE_SECRET` | Never copy values or key files into documentation/exports. |
| Isolated tests | `ORGCHAIN_TEST_DATABASE`, `ORGCHAIN_TEST_ORGCHAIN_DATABASE`, `LARAVEL_STORAGE_PATH` | [[Testing and Quality Assurance Guide]]; also isolate normal/native DB and cache configuration for actual processes. |
| Private fixture provisioning | `OFFICE_SEED_PASSWORD`, `STUDENT_DEMO_PASSWORD`, `SYSTEM_ADMIN_PASSWORD`, voting seed-password settings | No public password defaults; do not seed populated user databases. |

Configuration caching can make a running process ignore later `.env` edits until an authorized cache rebuild. Changing a value in a note does not configure any service. Never assume the office path or provider settings on one laptop match the VPS.

See [[Local Development Setup Laragon PHP 8.3]], [[Session Management and Lifetime Policies]] and [[VPS Pilot Deployment and Status]].
