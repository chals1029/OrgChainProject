---
title: Interactive Activity Calendar
tags:
  - office
  - calendar
  - schedule
  - activities
created: 2026-08-20
status: active
---

# 📅 Interactive Activity Calendar

> [!info] Campus Calendar
> The read-only calendar at `/office-desk/calendar` shows persisted activities permitted for the signed-in desk. SO accounts stay within their assigned organization; institutional desks see packages only once their workflow stage permits access. It displays schedules, not a venue-conflict validation or approval action.

---

## Calendar status and scheduled timing

Approval status comes from the persisted workflow: pending review, returned, OC approved or completed. Upcoming/ongoing/past timing is derived separately from start/end timestamps; a future schedule does not prove approval. Multi-day events occupy each relevant day, with a midnight-exclusive end.

---

## 🗓️ Dynamic Month Filtering

The controller parses `month=YYYY-MM`, literal activity/venue search `q`, and scope/status/timing filters. Invalid months retain the current month. Today and Month are the only view controls on desktop and mobile; Agenda, its query state and mobile automatic switching are removed. Ongoing and Upcoming panels sit below the full-width month grid.

Enter submits search without a Search button. Clearing the text manually or with the native search-clear control restores unsearched results while preserving month and other filters. Scope/status/timing selects still apply immediately. Continuation arrows are omitted; full start/end ranges remain in the event details.

---

## ⚡ No-reload month navigation (2026-09-19)

`public/js/org-calendar.js` fetches the target month/filter result and replaces the calendar root, including its month grid, below-calendar lists and event JSON. It updates the URL with `pushState`, retains focus, and falls back to ordinary GET navigation on failure. Browser back/forward reloads the matching URL. Search does not request on ordinary typing until Enter or clearing an applied query.

## 🪟 Schedule detail modal (2026-09-19)

Clicking a day cell (or an Upcoming item) opens a viewport-centered `<dialog>` (`position:fixed; inset:0; margin:auto`):
- **1 event** → full details immediately (status, date, time, venue, notes, link to Activities)
- **Multiple events** → pick-list with a "← All that day" back button
- **Empty day** → "No activities on this day"
- The old selected-day card below the calendar was removed; empty months no longer 500 (null-safe default-day fallback)
