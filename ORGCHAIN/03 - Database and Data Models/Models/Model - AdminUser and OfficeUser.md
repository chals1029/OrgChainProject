---
title: Model - AdminUser & OfficeUser
tags: [models, users, auth]
created: 2026-08-20
updated: 2026-10-16
---

# Model: AdminUser & OfficeUser

Related: [[Eloquent and Core Models Reference]] · [[Database Schema and ERD]] · [[Model - Student and UserAccount]]

## `OfficeUser`: Eloquent office workflow account

- **Class:** `App\Models\OfficeUser`, an Eloquent authenticatable model on `office_users`, using the inherited/default connection and separate **`office` guard**.
- **Identity/account fields:** `id`, `name`, `email`, `username`, `office_title`, `employee_id`, `office_role`, `student_organization_id`, `is_active`, `must_change_password`, `auth_version`. Passwords are hashed through the `password` cast; password and remember-token attributes are hidden from serialization.
- **Organization relationship:** `studentOrganization()` belongs to `StudentOrganization` through `student_organization_id`; `organizationName()` reads that related organization's name. The local `votingsystem.office_users` export has a physical FK to `votingsystem.student_organizations.id`, not the older `orgchain.student_organizations.org_id` table.
- **Office desk access:** used by SO accounts as well as office reviewers. The office-authentication middleware requires an active account; an SO account must have an active named organization assignment. `must_change_password` restricts desk use until the password-change flow is completed.

### Complete role set

| Stored role code | Model label / purpose |
| --- | --- |
| `so` | Student Organization (SO) |
| `oso` | Office of Student Organization (OSO) |
| `sdo` | Sustainable Development Office (SDO) |
| `ovcaa` | OVCAA |
| `oc` | **Office of the Chancellor (OC)**, including final proposal review |

Roles are authorization values on one model, not separate account tables. OSO verifies TOSA documents; OVCAA may have read-only access. Legacy OVCAA evaluator labels no longer grant editing. Other roles have no TOSA access.

### Credential revocation and session binding

- The model's Eloquent `updating` hook detects changes to **password, email or active state**. Unless `auth_version` is already being changed in that update, it calls `revokeCredentials()`.
- `revokeCredentials()` increments `auth_version` and rotates the remember token. The mutation is persisted by the surrounding model save; it is not an independent database transaction.
- `bindCurrentSession()` binds the office guard to the account and writes the account ID/version into `office_auth_state`, refreshing remembered authentication when appropriate.
- `EnsureOfficeAuthenticated` re-fetches the account and checks active state and the bound version, or a freshly authenticated current remember token. Missing/inactive accounts and stale bindings are rejected. Pre-version sessions are accepted only while the account's version remains zero.

This describes model saves and middleware enforcement; it does not promise that arbitrary raw SQL or bulk query updates invoke Eloquent model events. Neither this note nor the schema export includes session tokens, password values or account records.

## `AdminUser`: separate native PDO voting identity

- **Class:** `App\VotingSystem\Models\AdminUser`, under `app/VotingSystem/Models/`; not an Eloquent alias for `OfficeUser`.
- **Table:** `admin_users`, represented as `votingsystem.admin_users` in the local snapshot.
- **Schema fields:** `id`, `name`, `email`, `password_hash`, `role`, `is_active`, `created_at`; local email uniqueness is a database index.
- **Role codes:** `admin`, `canvassing`, `view_only`, distinct from SO/OSO/SDO/OVCAA/OC office roles.
- **Behavior:** native PDO methods retrieve voting staff accounts and manage staff records. Voting identity is not supplied by Laravel's office guard or `UserAccount` student provider.

`App\Models\SystemAdminUser` and its `system_admin` guard form another separate identity boundary. Do not confuse voting `admin_users`, office `office_users` and `system_admin_users` merely because each can have administrative responsibilities. Account provisioning and login instructions belong in the operational guides, not a table of example credentials in this model note.

## Source evidence and schema boundary

`app/Models/OfficeUser.php`; `app/Http/Middleware/EnsureOfficeAuthenticated.php`; `config/auth.php`; `app/VotingSystem/Models/AdminUser.php`. Physical evidence: [[ERD Exports/orgchain-local-schema.sql|local schema-only SQL]]. The schema guide distinguishes the local export from current source and live production; no runtime authentication checks or database changes are performed by this documentation update.
