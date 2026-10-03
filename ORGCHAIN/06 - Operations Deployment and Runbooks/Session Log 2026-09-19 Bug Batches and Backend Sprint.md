---
title: Session Log 2026-09-19 Bug Batches and Backend Sprint
tags:
  - session
  - changelog
  - preoral
created: 2026-09-19
---

# Session Log — 2026-09-19

## Shipped

### Teammate bug batches 1–3
- **Batch 1 (Student + Community)**: greeting comma fix, read-only profile, Student Voice flow, clickable activity modals, community views/feelings/audience, fake posts removed, dashboard search bar removed
- **Batch 2 (SO Dashboard/Analytics/Calendar/Budget)**: cards-before-pipeline order, live SO charts, calendar null-date crash fixed, receipt UX, chain-hash display wording
- **Batch 3 (Financial/AR/Updates/Renewal/TOSA)**: real `.docx` generation via `DocxBuilder` (`ZipArchive`, no Composer dep), in-page Word preview (`docx-preview` + `jszip` vendored), real `office_announcements` / `office_templates` tables + store/download routes
- Office sidebar uses full page loads (fetch-and-swap was leaving Upload/Camera dead); top progress-bar loader + hover-prefetch added so tabs still feel instant — see [[High-Level Technical Architecture]]

### Calendar upgrades
- No-reload month arrows (`loadMonth()` + `pushState`, fallback to full load), viewport-centered schedule modal (1 event → details, many → pick-list, empty → notice), selected-day card removed, empty-month 500 fixed — see [[Interactive Activity Calendar]]

### Analytics real export
- Fake `alert()` Export replaced with `GET /office-desk/analytics/export` → live CSV (health, college table, activity financials) from shared `buildAnalyticsData()` — see [[Office Portal API and Action Endpoints]]

### Budget page goes live
- `buildLiveBudgetDataset()`: KPIs, bar chart, expense table, docs, timeline, stepper all read `org_activities` + `budget_items` + `expense_receipt_reviews` + chain blocks; demo registry only as empty-state fallback
- Expense donut reduced to **In-Campus vs Off-Campus** scope split from live sums
- `GET /office-desk/budget-utilization/receipts/{review}/view` opens real sealed receipt files; View buttons wired, empty states honest — see [[Budget Utilization and OCR Receipts]]

### Recognized-org registry (32 orgs, AY 2025–2026)
- Source: OSO ARASOF-Nasugbu PDF list; staged in `config/student_orgs.php`, then promoted to **`student_organizations` table** + `StudentOrganizationSeeder` (32 rows, idempotent)
- **No email column by design** — real org emails never stored
- Drives Renewal picker, Budget filter, Activity autocomplete — see [[Database Schema and ERD]], [[Database Seeders and Demo Fixtures]]

### SO backend audit
- Full desk audit: Budget page was the biggest DB gap (now closed); remaining demo-only items queued next — dashboard KPI cards (hardcoded 5/2/2/1), Pending Action Items, activity-doc Preview/Replace/Delete `alert()`s, financial ledger copy-viewer `alert()`s, frontend-only Settings modal, hardcoded Analytics health/compliance constants

### OSO backend audit (2026-09-20)
- New `buildOsoOverview()`: all `org_activities` become filterable rows (org short name, scope side, status bucket, AY/semester/month); dashboard KPIs, approval donut + legend, scope panel, 10-month trend, top-5 org ranking, action transactions, type breakdown, processing-time bars, and return-cause list all aggregate from these rows — the fabricated `osoFilterData` object (48 subs, 27/11/7/3 donut, multiplier math) and the fake ranking orgs (JPICE/JPCS/AECES were never in the registry) are gone
- OSO filter bar now filters **real rows** (AY/semester/month/scope); historical years honestly show empty states instead of invented history
- Trend chart formula (`4 + $i + …`) replaced with real monthly counts from `created_at`; `?:` demo floors removed from type counts
- Renewal packets finally actionable: OSO **Approve / Return** on incoming submissions (`POST /office-desk/renewal/submissions/{submission}/review`, new `review_remarks` + `reviewed_at` columns, remarks asked via prompt on return, SO can resubmit after a return)
- Processing-time chart shows measured averages (proposals 0.7d from created→decided; renewals when reviewed; other tracks render as gaps, not fake 1.8–3.2d bars); return causes mined from real reviewer remarks with honest empty state
- Recent Updates card (SO branch) now reads latest office announcements + latest activity movements instead of 3 static items

### Student portal backend audit (2026-09-20)
- **TOSA applications now real**: uploads persist to `tosa_applicants.requirements` (`storage/app/public/tosa/{sr}/`), View/Replace/Remove all hit the backend (`POST /portal/tosa/documents`, `DELETE /portal/tosa/documents/{key}`), Submit requires all 5 docs and moves the applicant to `screening` (`POST /portal/tosa/submit`); 5-step tracker, header pill, and submit row are server-rendered from the applicant's real `subsection` (returned applicants can revise + resubmit); deleted ~200 lines of DOM-only TOSA JS from the portal layout
- **RSVP now real**: new `activity_registrations` table + `capacity` column on `org_activities` (migration `2026_09_20_000007`), `POST /portal/activities/{activity}/rsvp` toggle endpoint (refuses completed activities), counts/year-level breakdowns/my-RSVP preloaded in `portal()`; modal shows real registration counts, "Open slots" when no capacity set, honest empty breakdown/photos/budget states
- **Activity modal detox**: removed fabricated 185/250 participant counts, 55/45 year split, recycled SSC stock photos, 45/35/20 budget splits, and `md5()`-generated fake TX hashes — budget tab now shows the activity's real approved/implemented figures, top-3 real org budget items, and the live chain head hash (or "Pending seal"); `Math.random()` TX fallback gone
- **Bulletins feed live**: the 9 hardcoded announcement rows and 3 hardcoded dashboard notices are gone — feed merges `office_announcements` (with TOSA-keyword detection + type→category mapping), upcoming activities, and the open renewal window into dated entries; pagination counts are server-seeded
- Removed the 10 fake sample activities fallback (honest empty state instead), the lingering "John Dela Cruz" comment fallback, and both share `alert()`s (now use the shared toast)

### Cross-desk connection check (2026-09-20)
- Live round-trip probe (insert → read from the other side's real query → cleanup): all 6 flows **CONNECTED** — student feedback → OSO Student Voice; OSO announcement → student bulletins + dashboard notices; student TOSA upload → OSO applicant queue; student RSVP → office-readable registrations; OSO renewal approve/return → SO renewal page; SO activity → student activities feed
- Closed the last one-way gap the probe exposed: office desks could not *see* RSVPs, so the activity detail page now has a **Registered Participants roster** (`rsvp_count` + `rsvp_roster` in `orgActivitiesList()`, latest 20 with name/SR/year/date)

### OSO Student Reports verification queue (2026-09-20)
- New OSO-only **Student Reports** tab (`GET /office-desk/student-reports`): stat cards (total/pending/verified/dismissed), status/college/topic filters, and a verification queue table over `student_feedback`
- Verify / Dismiss actions (`POST …/review`, new `status` + `review_note` + `verified_by` + `verified_at` columns, migration `2026_09_20_000008`) with a sidebar badge showing the live pending count; dashboard Student Voice links into the queue
- Students see their own submissions' statuses ("My recent reports" with Pending/Verified/Dismissed pills under the Student Voice form)
- Render-tested as OSO with seeded rows: page renders, verify records status + reviewer + timestamp

## Vault updates
- Budget, Calendar, Schema/ERD, Seeders, BudgetItem model, API endpoints, Route map refreshed (this log links them)

## Still open
- Frontend-only Settings modal (needs its own scope: password/PIN changes, officer management)
- (Closed 2026-09-20) Activity docs table now real: rows are the official on-disk templates (Preview = real download) plus actually-uploaded files (Preview + working DELETE); bulk `supporting_documents[]` uploads are now ingested (they were silently dropped — the controller only read `attachments`), required-checklist matching works off filenames, and `saveActivity()` propagates org/college/scope + `college_review` onto the activity row
- (Closed 2026-09-20) Financial ledger + supporting docs go live: sealed receipts prepend as real rows with working file links (demo rows keep the popup only when no file exists)
- (Closed 2026-09-20) Every recognized org now has backend sample data (`OrgSampleDataSeeder`, idempotent): legacy free-text org names unified to canonical registry names, then per org — 1 activity (varied scope/status/budget), 2 budget items, 1 fund account + 2 sources, 2 compliance docs. Verified: 32/32 orgs covered (38 activities, 60 items, 32 fund accounts, 72 docs)
- (Closed 2026-09-20) Last `alert()`-only buttons wired: Activities detail Read opens the real uploaded file matched by `doc_key` (honest "No file uploaded yet" otherwise); Accomplishment dossier opens the real activity page; leftover demo-row popups replaced with muted honest states
- (Closed 2026-09-20) Analytics health/compliance now computed: `healthScore = 0.6·budgetHealth + 0.4·completion` where `budgetHealth = 100 − |burnRate − 75|·2`; `complianceRate = approvedDocs/totalDocs` (100 when no docs yet)
