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

3. **Organization Renewal & Qualification**:
   - *Current State*: OSO owns the filing window, manages qualification status (Active vs. Inactive, Qualified vs. Disqualified with reasons) across all 32 student organizations, and reviews incoming packets via an expandable document inspection drawer with in-browser preview, approval, and return actions.
   - *Current Boundary*: Adviser and Dean are verified names entered in the packet rather than independent sequential login accounts; recognition approval is finalized by OSO.
   - *See*: [[Organization Renewal Filing Window]]

4. **Office nav vs controller gates**:
   - *Current State*: Archive / TOSA hidden in nav for non-OSO, but many URLs still return **200** if opened directly.
   - *Renewal* already enforces **403** for SDO/OVCAA.
   - *Todo*: `abort_unless` on Archive/TOSA (and other OSO-only desks).

5. **Playwright smoke ≠ full E2E**:
   - Covers load + console/HTTP across roles; does not yet automate Advance chain or full renewal submit with all files.
   - `/voting-system` can hang under Playwright load waits while plain HTTP is fine.
   - *See*: [[Playwright Console Smoke Testing]]

6. **In-Browser Document (.docx) Editing & Physical Signature Delimitation**:
   - *Delimitation*: In-browser native Word editing is intentionally delimited from the current system scope. Official university templates (`.docx`, `.pdf`, `.xlsx`) are provided for direct download, and submitted files are previewed via built-in in-browser viewers.
   - *Technical Rationale*: OpenXML (.docx) is a complex zipped package of XML schemas. Web HTML rich-text editors (e.g., TinyMCE, Quill) strip and scramble official university ISO headers, Red Spartan letterheads, multi-column signature grids, and strict legal tables. Native Word conversion APIs (such as Tiny Cloud Enterprise or Microsoft 365 WOPI) require costly recurring commercial licenses.
   - *Data Privacy & Legal Compliance (RA 10173)*: Under the Philippine Data Privacy Act and university accreditation rules, high-stakes documents require authentic **physical wet-ink signatures** from the Faculty Adviser, College Dean, and Council Officers to eliminate risk of digital signature forgery.
   - *Supported Workflow*: **Download Official Template (.docx)** &rarr; **Edit Locally in Microsoft Word** &rarr; **Print & Collect Physical Signatures** &rarr; **Scan / Photo (PDF/Image) & Upload** &rarr; **In-Browser OSO Inspection & Approval**.
   - *Future Recommendation*: Integration with a dedicated self-hosted document server (e.g., OnlyOffice or Collabora Online) should the university provision enterprise infrastructure in future phases.

