---
title: Session Management and Lifetime Policies
tags: [security, sessions, auth, lifecycle]
created: 2026-08-20
updated: 2026-10-16
status: active
---

# Session Management and Lifetime Policies

Do not apply one subsystem's session settings to every user. Laravel portal guards, system administration and native voting maintain distinct authentication state.

## Laravel portals

- The `student` guard resolves `UserAccount` records; the `office` guard resolves `OfficeUser` records.
- `config/session.php` controls the selected session driver, lifetime, cookie scope and secure-cookie behavior. An idle/absolute timer is not a personal portal preference.
- Office middleware re-reads the account and checks active status plus `office_auth_state` against `auth_version`. Disabling, credential changes and officer turnover can revoke existing sessions/remembered authentication.
- SO requires an active assigned organization. A new/reset temporary password must be changed before normal office actions.
- Authentication success for one guard does not grant another guard's role or ownership rights.

## Native voting

The active voting closure in `routes/web.php` mirrors Laravel session entries into `$_SESSION`, seeds `_csrf` from Laravel's `csrf_token()` when absent, and registers a shutdown save back into the Laravel session. `App\VotingSystem\Kernel` initializes native session state when needed; native voting handlers validate their own `_csrf` input. The voting route's Laravel CSRF-middleware exception does not mean CSRF checks are absent. Do not assume fully separate session storage, or infer shared login permissions from this bridge.

`config/voting.php` declares native security defaults of 1,800 idle seconds and 28,800 absolute seconds; private environment configuration can override them. These are native voting settings, not universal Laravel session lifetimes.

## Safe operation

Preserve the existing application key and secure cookie configuration. Do not dump session contents into the vault, use another person's account for smoke testing or clear production session/cache data as a normal UI deployment step. Test sessions belong in isolated temporary storage and must be removed after the test.

Source: `config/auth.php`, `config/session.php`, `config/voting.php`, `routes/web.php`, `EnsureOfficeAuthenticated` and `app/VotingSystem/Kernel.php`. See [[Security Architecture and Guardrails]] and [[6-Digit Email OTP Verification]].
