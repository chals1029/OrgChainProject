---
title: Session Management & Lifetime Policies
tags:
  - security
  - sessions
  - auth
  - lifecycle
created: 2026-08-20
status: active
---

# 🛡️ Session Management & Lifetime Policies

> [!info] Dual-Kernel Session Bridge
> OrgChain bridges standard Laravel HTTP sessions with the standalone native PDO Voting System Kernel to ensure seamless cross-subsystem navigation without session loss.

---

## ⏱️ Session Lifetime Rules

```mermaid
stateDiagram-v2
    [*] --> ActiveSession: Login Verified
    ActiveSession --> ActiveSession: User Activity (Resets Idle Timer)
    
    ActiveSession --> IdleTimeout: 30 Minutes Inactivity (1800s)
    ActiveSession --> AbsoluteTimeout: 8 Hours Total Duration (28800s)
    
    IdleTimeout --> Terminated: Session Destroyed
    AbsoluteTimeout --> Terminated: Forced Re-authentication
    Terminated --> [*]
```

---

## 🔄 The Voting System Session Bridge

Because the Voting System runs under its own fast Kernel (`app/VotingSystem/Kernel.php`), `routes/web.php` executes a bidirectional session synchronization bridge:
1. Synchronizes `session()->all()` into `$_SESSION` before executing `VotingKernel::handle()`.
2. Registers a `register_shutdown_function` to write back any changes from `$_SESSION` back into Laravel's session store before exiting.
3. Automatically shares CSRF tokens across both ecosystems (`_csrf`).
