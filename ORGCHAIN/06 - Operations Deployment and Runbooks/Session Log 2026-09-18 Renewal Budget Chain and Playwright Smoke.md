---
title: Session Log 2026-09-18 Renewal Budget Chain and Playwright Smoke
tags:
  - session
  - changelog
  - preoral
created: 2026-09-18
---

# Session Log — 2026-09-18

## Shipped

### Budget utilization on chain + student visibility
- `BudgetChainService` — 3-node JSONL seals under `storage/app/orgchain/budget/node-{1,2,3}/`
- Expense receipt submit seals hash; office Budget page shows chain table
- Student portal Budget Utilization shows public on-chain seals

### Organization Renewal (first cut)
- OSO-only open/close filing window
- SO Renewal tab locked until OSO opens
- 10-document checklist, draft/submit, uploads
- SDO/OVCAA aborted from renewal route
- See [[Organization Renewal Filing Window]]

### Playwright full-system smoke
- `scripts/console-smoke.mjs` covers public + SO/OSO/SDO/OVCAA + student
- See [[Playwright Console Smoke Testing]]

## Vault updates
- Quick Reference logins/paths refreshed
- Renewal + Playwright notes added; MOC / route map / QA guide linked

## Still open
- Adviser/Dean approval roles for renewal chain
- Controller-level lock on Archive/TOSA for non-OSO
- Deeper E2E journeys beyond smoke
