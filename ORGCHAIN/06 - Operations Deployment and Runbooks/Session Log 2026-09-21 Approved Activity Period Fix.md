---
title: Session Log 2026-09-21 Approved Activity Period Fix
date: 2026-09-21
tags: [office, budget, workflow, frontend, operations]
status: implemented
---

# Align the budget page with approved activity periods

## Finding

The live database already contains final-approved activities. The budget page selected the first approved row, **Innovation Fair Booth Series**, which starts in July 2026 and belongs to A.Y. 2025–2026 Midyear. The page filter defaulted to A.Y. 2026–2027 Annual, so the activity card showed no matching records and zero budget even though the activity was approved.

## Fix

- SO activity options are filtered to the selected academic year and semester.
- The default activity is selected from the same reporting period.
- Deep links with an `activity_id` from another academic year redirect to that activity's correct annual period.
- The existing final-approval gate remains unchanged: only `workflow_status = oc_approved` activities are supplied to Budget Utilization.

## Live evidence

After reload, Chrome now opens on **BatStateU Sportsfest 2026**, A.Y. 2026–2027 Annual, with **Approved Budget ₱40,000**, **Actual Expenses ₱20,000**, and **Remaining Balance ₱20,000**. The old false “No matching scope” state is gone.

## Verification

- `BudgetUtilizationTest`: **8 passed**.
- Blade view cache passed.
- Chrome live page confirmed the selector, activity details, and budget values align with the selected period.
