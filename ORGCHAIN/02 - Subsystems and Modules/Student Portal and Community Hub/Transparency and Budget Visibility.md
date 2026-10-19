---
title: Transparency & Budget Visibility
tags:
  - student
  - transparency
  - budget
  - governance
created: 2026-08-20
status: active
---

# Transparency and Budget Visibility

The authenticated Student Portal (`/portal`) combines organization-budget visibility, activity information, persisted OSO publications and private feedback history. Settings contains **Profile settings**, not a Compact view toggle.

## Activity details

Both grid and list detail dialogs omit the unsupported attendee year-demographic chart, QR/ledger participation attestation and RSVP/certificate panel. Their hardcoded breakdowns, browser-only registration mutations/toasts and exclusive styles were removed. Remaining tabs and backend registration records/endpoints are unchanged; no historical attendance data was deleted.

## SDG highlights

- A compact panel displays coloured goal symbols, the actual activity count and up to three activity titles per goal. Symbols and colours share `SdgCatalogue` and the existing activity-creation icon partial.
- The source remains the latest 12 final-approved SDG-tagged activities within the selected department. These counts are not an all-time campus-wide total. No source records or tag assignments are changed.
- Search matches goal labels/names or any activity title in each included group, including titles outside the initial preview. Activity-title searches narrow the displayed activity count/titles; goal-name searches retain that goal's complete group. The SDG selector intersects with search.
- **Clear filters** resets only this panel. A live goal count and distinct no-data/no-match states reflect the current selection. Search does not submit the department, community or feedback forms.
- Cards stack on phones. Without JavaScript, all server-rendered goal cards remain readable and nonfunctional filter controls stay hidden.

## Budget category breakdown

- Compact **In-Campus / Off-Campus** rows use circular scope icons, inline desktop bars/percentages, and separate **Actual** / **Total / Budget** columns with dividers and a category count. Narrow cards place the bar below the label/amounts without horizontal overflow. Values remain the actual scope totals, not the example amounts in the design reference.
- The budget gauge and breakdown align to equal desktop row heights with consistent section/card gutters. Upcoming/recent activity and announcement footers use compact spacing; those three cards stack on tablet/mobile without changing data or five-row pagination.
- `StudentPortalController::portal()` sums `org_activities.approved_budget` and `implemented_budget` from student-visible final-approved activities. Legacy `budget_items` categories are not added again to these allocations.
- No sample category amounts or demonstration pie-chart values establish current spending.
- Public receipt summaries include sealed eligible receipt rows linked to student-visible activities, with item amounts, hashes and node counts. Pending seals are not presented as confirmed public receipts.
- Student transparency does not grant access to private receipt originals or office mutations. Receipt seal verification retains its existing verification terminology and endpoint: `GET /portal/expenses/{receipt}/verify`.

Office recording and receipt integrity: [[Budget Utilization and OCR Receipts|Budget Utilization and Receipt Records]].

## Published notices and deep links

Announcements, dashboard updates and notification links share `OfficeAnnouncement::studentPublication()` metadata: stored title, type, body, author, priority and publication time. Body keywords do not recategorize a notice.

| Persisted type | Student bulletin filter |
| --- | --- |
| General / General Announcement | General Announcements |
| Deadline / Deadline Notice / Reminder / Compliance Reminder | Deadline Reminders |
| TOSA / Call for TOSA | Call for TOSA |
| Other OSO publication types | OSO Notices |

`/portal?announcement={id}` opens the requested announcement. The office counterpart is `/office-desk/updates?announcement={id}`; **Copy Link** copies the actual notice URL rather than merely showing a success message.

The office notice popup footer contains **Close** only, without Copy Link or acknowledgment actions. Existing timestamped acknowledgment receipts remain preserved; the backend receipt endpoint and student feedback acknowledgment are unchanged.

Notice-directory copy icons still copy canonical notice URLs through the Clipboard API, a copy-command field and then a manual-copy prompt when permissions block both. Copy feedback uses accessible live status; dialog hosting/close handling remains available for other office notifications.

> [!important] Local schema boundary
> The updated office notice page requires `2026_10_15_000001_create_office_announcement_acknowledgments_table`. It is deployed on the VPS but absent from the existing local structure-only ERD export. These notes do not apply migrations or claim the local export is production schema.

## Student feedback and owner-only status

1. A student sends campus feedback through `POST /portal/feedback`; it is recorded for OSO review, not published to the community feed.
2. OSO opens `/office-desk/student-reports` and chooses **Acknowledge report** or **Dismiss**. The mutation route remains `POST /office-desk/student-reports/{report}/review`, with `decision=acknowledged|dismissed`.
3. Acknowledgment preserves the first OSO reviewer/time and optional review note. Repeated review does not overwrite the original decision.
4. Only the submitting student sees that feedback in their history and receives its acknowledgment notification, linking to `/portal#feedback-{id}`. Dismissal is not acknowledgment and produces no acknowledgment notification.

Historical persistence names are intentionally retained: acknowledged feedback uses `status=verified`, `verified_by` and `verified_at`. The user-facing wording is **Acknowledged**, not a claim that OSO verified the feedback's truth. Receipt seals, AR/FR verification and evaluator evidence verification keep their own terminology.

## Confirmations and related workflows

Business submissions/reviews/deletions/settings changes require confirmation. Cancellation leaves drafts and selected files intact; read-only navigation, previews/downloads and local file selection/cancellation do not prompt. Office logo/template selection is staged until explicit upload/save.

Related: [[Community Feed Posts and Likes]] · [[Student Profile and Authentication]] · [[TOSA Application and Review Workflow]] · [[Office Portal API and Action Endpoints]] · [[Web and Portal Route Map]]

## Source evidence

`README.md` (student portal); `app/Models/OfficeAnnouncement.php`; `StudentPortalController::portal()` / `storeFeedback()`; `OfficePortalController::acknowledgeAnnouncement()` / `reviewStudentReport()`; portal/Updates views; `routes/web.php`.
