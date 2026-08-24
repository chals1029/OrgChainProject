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
> The interactive calendar at `/office-desk/calendar` aggregates all approved and upcoming activities across university organizations, preventing venue conflicts and schedule overlaps.

---

## 🎨 Calendar Status Color Codes

```mermaid
graph TD
    UP["🟢 Upcoming Activities<br/>Status: 'upcoming' / 'ovcaa_approved'"]
    ON["🟡 Ongoing Activities<br/>Status: 'ongoing'"]
    COMP["⚪ Completed Activities<br/>Status: 'completed'"]
    PEND["🟠 In Review / Verification<br/>Status: 'verification' / 'pending'"]
```

---

## 🗓️ Dynamic Month Filtering

The calendar controller dynamically parses query parameters `?month=YYYY-MM`:
- Calculates monthly event densities.
- Maps multi-day activities (`starts_at` to `ends_at`).
- Links each calendar event directly to its detailed proposal and budget sheets.
