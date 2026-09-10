---
title: Session Log 2026-09-10 Portal Responsiveness and Mobile Fixes
tags: [session-log, frontend, responsive, mobile, student-portal, office-portal]
created: 2026-09-10
status: active
---

# 📝 Session Log — 2026-09-10 Portal Responsiveness & Mobile Fixes

> [!abstract] Session Summary
> No-reload sidebar navigation across all portals, student-portal mobile overhaul, working settings/notification dropdowns, community likes + comments viewing, SO budget OCR upload, TOSA/updates cleanup, and the mobile sidebar overlay stacking-context fix. Nothing pushed — all local.

---

## 🧭 Overall — No-Reload Sidebar Navigation

- `orgNavigate()` in [[Multi-Tier Office Roles SO OSO SDO OVCAA|office layout]] and the portal equivalent fetch pages via `X-Requested-With: XMLHttpRequest` and swap `.org-topbar` / `.org-content` (portal: topbar + content) without full reloads.
- Active link re-marking, `history.pushState`, page loader bar (`org-page-loader`), scroll-to-top on navigate.
- Hamburger (`org-menu-toggle` / `sp-menu-toggle`) + overlay (`org-sidebar-overlay` / `sp-overlay`) drawer on `≤900px`/`≤992px`.
- **Major bug — transparent-black overlay blocking tabs:** caused by `z-index: 1` on `.org-shell` / `.sp-shell` creating a stacking context that trapped the drawer behind its own overlay. Fixed by removing the redundant `z-index`; drawer now sits at `z-index: 999` above the overlay.
- Drawer auto-closes on desktop resize via `matchMedia('(min-width: 901px)')` listener so no scroll-lock leaks into desktop layout.

## 🎓 Student Portal (`/portal`)

- **Mobile overhaul (this session):** hero stacks to 1 column `≤640px`; topbar gap/title/buttons tighten `≤640px`/`≤540px`; reaction dock scrolls instead of overflowing `≤480px`; post-action buttons compact; comment composer hides GIF/Sticker tools on tiny screens; composer popovers capped to viewport; tables scroll inside cards; long words wrap (`overflow-wrap: anywhere`).
- Settings ⚙️ + bell 🔔 moved into clickable working dropdowns (`spSettingsDropdown`, `spBellDropdown`): profile-settings modal (`PUT portal.profile.update`), compact-view toggle, notifications list with mark-all-read.
- Community feed: likes open a likers modal (`GET community.posts.likers` → `CommunityFeedController@likers`); comments show count button, view-all/show-less past 5; comment posting + threaded display via AJAX.
- Search box hidden on phones (`≤540px`); user pill collapses to avatar.

## 🏢 SO Side (Office Portal)

- Settings entry removed from the SO sidebar (kept for OSO/OVCAA only).
- Sidebar nav icons color-coded (`is-ico-red/violet/blue/green/gold/teal/slate/maroon`).
- [[Budget Utilization and OCR Receipts|Budget Utilization]]: SO-only "Record Expense" card with Tesseract.js OCR pre-fill, review-confirm checkbox, submits to `storeReceiptReview` (`ExpenseReceiptReview` model).
- [[Interactive Activity Calendar|Calendar]]: date click opens activity detail popup fed by pipeline + `OrgActivity` data.
- [[Document Archive and Compliance Repository|Archive]]: working New Folder + Upload modals (`ArchiveFolder`, `ArchiveDocument` models).
- Activity/event location options now include **gymnasium, mini forest, Taal building** (replaced eco park set) on both student forms and office proposal labels.

## 🗂️ OSO Side

- TOSA: "File Format Settings Panel" (palm/settings) removed from inside requirements.
- [[Activity Proposal Approval Pipeline|Updates]]: announcement title field removed — title auto-derived from body + type.
- In-campus guided workflow: activity-type select → info form → TinyMCE core-document editing → conditional attachments → save draft / submit (`InCampusActivitySubmission` model).

## 🌱 SDO Side

- Settings entry removed from the SDO sidebar.
- SDO checklist + OVCAA approval-trail grids converted from fixed inline grids to responsive classes (stack `≤640px`) — previously unreadable on phones.

## 🗄️ Auth & Data (context, unchanged this session)

- Students authenticate against `orgchain.user_accounts` via `sr_code` + 6-digit email OTP (no passwords); Google institutional OAuth (`@g.batstate-u.edu.ph`) also supported. See [[Student Profile and Authentication]] and [[6-Digit Email OTP Verification]].

## ⏭️ Follow-ups

- Test matrix: 360px / 768px / 1440px on `/portal`, `/office/*` (SO, OSO, SDO, OVCAA) — local servers running.
- Still local-only: commit + push pending user approval.
