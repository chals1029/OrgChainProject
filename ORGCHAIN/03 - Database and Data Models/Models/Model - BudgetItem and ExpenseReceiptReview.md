---
title: Model - BudgetItem & ExpenseReceiptReview
tags: [models, budget, ocr, finance]
created: 2026-08-20
---

# 💰 Model: BudgetItem & ExpenseReceiptReview

### 💵 `BudgetItem` (Eloquent)
- **Table**: `budget_items`
- **Fields**: `id`, `title`, `category`, `allocated`, `utilized`, `fiscal_year`, `notes`.

### 🧾 `ExpenseReceiptReview` (Eloquent)
- **Table**: `expense_receipt_reviews`
- **Fields**: `id`, `activity_title`, `item_name`, `category`, `quantity`, `unit_cost`, `expense_date`, `receipt_path`, `receipt_name`, `ocr_confidence`, `student_confirmed`, `verification_status` (`ready_for_review`, `approved`, `rejected`).
