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

3. **Organization Renewal — first cut only**:
   - *Current State*: OSO open/close window + SO 10-doc packet upload/submit. Adviser/Dean are **name fields**, not login roles.
   - *Gap*: No Adviser → Dean → OSO approval workflow UI yet; recognition status after OSO final approve not fully wired.
   - *See*: [[Organization Renewal Filing Window]]

4. **Office nav vs controller gates**:
   - *Current State*: Archive / TOSA hidden in nav for non-OSO, but many URLs still return **200** if opened directly.
   - *Renewal* already enforces **403** for SDO/OVCAA.
   - *Todo*: `abort_unless` on Archive/TOSA (and other OSO-only desks).

5. **Playwright smoke ≠ full E2E**:
   - Covers load + console/HTTP across roles; does not yet automate Advance chain or full renewal submit with all files.
   - `/voting-system` can hang under Playwright load waits while plain HTTP is fine.
   - *See*: [[Playwright Console Smoke Testing]]
