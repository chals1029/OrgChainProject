---
title: Budget Utilization & OCR Receipts
tags:
  - office
  - budget
  - ocr
  - receipts
  - finance
created: 2026-08-20
status: active
---

# 💰 Budget Utilization & OCR Receipts

> [!abstract] Financial Accountability
> OrgChain bridges organizational budgeting with automated Optical Character Recognition (OCR) receipt auditing to ensure 100% transparency in student fee utilization.

---

## 🧾 Expense Liquidation & OCR Pipeline

```mermaid
flowchart LR
    UP[📤 Upload Receipt Image] --> OCR[🔍 OCR Inspection & Parser]
    OCR --> SCORE[📊 Calculate OCR Confidence Score<br/>(e.g., 94%)]
    SCORE --> CONFIRM[👤 Student Affirmation Checkbox]
    CONFIRM --> QUEUE[📋 Expense Review Queue<br/><code>ExpenseReceiptReview</code>]
    QUEUE --> AUDIT{Admin / OSO Audit}
    AUDIT -- Approve --> UPDATE_BUDGET[💰 Deduct from BudgetItem.utilized]
    AUDIT -- Reject --> REASON[❌ Flag with Rejection Reason]
```

---

## 📊 BudgetItem Entity Attributes

From [`BudgetItem`](file:///c:/laragon/www/OrgChain/OrgChains/app/Models/BudgetItem.php):
- `title`: Budget category/activity title (e.g., *Leadership Summit*).
- `category`: `Programs`, `Extension`, `Operations`, `Sports`.
- `allocated`: Total approved budget allocation (PHP).
- `utilized`: Cumulative verified expenses (PHP).
- `fiscal_year`: Fiscal period (e.g., `2026`).

---

## 🔍 OCR Receipt Review Fields

From [`ExpenseReceiptReview`](file:///c:/laragon/www/OrgChain/OrgChains/app/Models/ExpenseReceiptReview.php):
- `activity_title`: Event associated with the expense.
- `item_name`: Purchased item or service.
- `unit_cost`: Verified monetary amount.
- `ocr_confidence`: Text recognition certainty percentage (0-100%).
- `student_confirmed`: Boolean flag confirming student officer verified the parsed data.
- `verification_status`: `ready_for_review`, `approved`, `rejected`.
