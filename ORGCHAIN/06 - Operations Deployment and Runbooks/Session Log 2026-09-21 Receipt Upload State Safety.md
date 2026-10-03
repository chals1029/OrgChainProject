---
title: Session Log 2026-09-21 Receipt Upload State Safety
date: 2026-09-21
tags: [office, budget, receipts, frontend, operations]
status: implemented
---

# Prevent restored receipts from appearing as new uploads

## Problem

The SO Budget Utilization form could display a previously selected receipt after a browser reload or back/forward restore. The browser restored the file input and prior scanner DOM state, so the page showed **Scan ready** even though the SO had not selected a receipt during the current form visit.

## Fix

Updated `public/js/receipt-scanner.js` to:

- clear a restored first-row file during initialization;
- reset all transient receipt state on `pageshow` when the page came from the back/forward cache;
- clear the preview, upload status, extracted values, scan UUID, validation message, and confirmation checkbox;
- keep the submit button disabled until the user selects and validates a receipt again.

The server-side scan ownership, exact-file hash, expiry, and final upload validation remain unchanged. No persisted receipt was removed.

## Verification

- `node --test tests/Unit/receipt-scanner.test.mjs`: **7 passed**.
- Chrome live reload of `/office-desk/budget-utilization` showed **No file selected** and **Waiting for receipt** after the previous stale screenshot selection.
- PHP syntax and `git diff --check` passed for the changed files.
