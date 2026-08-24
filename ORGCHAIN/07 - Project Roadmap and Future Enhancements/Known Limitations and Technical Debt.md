---
title: Known Limitations & Technical Debt
tags: [limitations, debt, architecture, notes]
created: 2026-08-20
---

# 📝 Known Limitations & Technical Debt

> [!abstract] Architectural Trade-offs
> Transparent documentation of design trade-offs and current system constraints.

---

## ⚠️ Current Architectural Constraints

1. **Local Single-Machine Multi-Node Ledger**:
   - *Current State*: The 3 blockchain nodes operate on the same filesystem (`storage/app/voting/chain/node-{1,2,3}`).
   - *Rationale*: Optimized for low operational overhead and simple single-server Laragon deployment.
   - *Future Upgrade*: Migration to separate network RPC socket servers for true physical distribution.

2. **Session Store Bridging**:
   - *Current State*: Session synchronization is managed between Laravel and the native PDO Voting Kernel via `routes/web.php`.
   - *Best Practice*: In future major refactors, merge all voting endpoints into standard Laravel controllers.
