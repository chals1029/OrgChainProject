---
title: Model - BudgetItem & ExpenseReceiptReview
tags: [models, budget, finance]
created: 2026-08-20
---

# 💰 Model: BudgetItem & ExpenseReceiptReview

### 💵 `BudgetItem` (Eloquent)
- **Table**: `budget_items`
- **Fields**: `id`, `title`, `category`, `allocated`, `utilized`, `fiscal_year`, `notes`, plus `college`, `organization_name`, `supplier`, `is_approved`, `scope` (`in_campus` default).

### 🧾 `ExpenseReceiptReview` (Eloquent)
- **Table**: `expense_receipt_reviews`
- **Fields**: `id`, `activity_title`, `item_name`, `category`, `quantity`, `unit_cost`, `expense_date`, `receipt_path`, `receipt_name`, `student_confirmed`, `verification_status` (`verified` for node-consensus receipts; legacy `approved`/`rejected` values may remain), plus `supplier`, `organization_name`, `receipt_reference`, `chain_hash`, `previous_hash`, `nodes_confirmed`.

> Live read-back: `OfficePortalController@budget` → `buildLiveBudgetDataset()` renders the Budget Utilization page (KPIs, bar chart, expense table, documents, timeline, scope-split donut) from these tables + `org_activities` + chain blocks. See [[Budget Utilization and Receipt Records]].
