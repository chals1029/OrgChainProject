---
title: Session Log 2026-09-21 Receipt Quality Gate
date: 2026-09-21
tags: [office, budget, ocr, receipts, validation, operations]
status: implemented
---

# Reject blurry or incomplete receipt photos

## Problem

The first OCR gate only required a minimum amount of recognized text and an overall OCR confidence score. A phone-gallery screenshot, cropped receipt, or photo missing key fields could therefore receive a scan token and continue to the SO review form.

## Fix

Receipt photo scans now require these four readable fields:

- merchant or supplier;
- total amount;
- transaction date;
- receipt, invoice, OR, or reference number.

Each required field must have a value and at least 80% field confidence. Receipt type and payment method remain reviewable because some paper receipts do not print those details.

The gate is enforced in both places:

1. `ReceiptScanner` rejects the image before creating a `receipt_scans` token.
2. `validatedMetadata` rejects any legacy partial scan before an expense can be stored.

The browser checks the same required fields and displays **Receipt is blurry or incomplete** with a retry action and disabled submit state.

## Verification

- `ReceiptScannerTest` and `ReceiptFieldExtractorTest`: **19 passed, 1 expected integration skip**.
- `node --test tests/Unit/receipt-scanner.test.mjs`: **7 passed**.
- The regression fixture representing a phone-gallery screenshot receives no scan token.
- Existing valid GCash, paper-receipt, batch, DOCX, and PDF paths remain covered.
