---
title: Middleware & Request Filters
tags: [middleware, security, auth, filters]
created: 2026-08-20
---

# 🛡️ Middleware & Request Filters

### 1. `EnsureStudentAuthenticated`
- Verifies that `Auth::guard('student')->check()` evaluates to `true`.
- Redirects unauthenticated users to the homepage login modal.

### 2. `EnsureOfficeAuthenticated`
- Verifies that `Auth::guard('office')->check()` evaluates to `true`.
- Redirects unauthenticated staff to `config('orgchain.office_login_path')`.

### 3. `SecurityGuard`
- Intercepts all requests before controller execution.
- Evaluates rate windows, scanner probes, and SQL injection signatures.
