---
title: Session Log 2026-09-21 Receipt Scanner Upgrade
date: 2026-09-21
tags: [office, budget, ocr, receipts, operations]
status: implemented
---

# Budget photo scanner upgrade

## What changed

Replaced the browser-only Tesseract/first-line/last-number guesses in the SO Budget Utilization form with local server OCR plus conservative field extraction. No paid API key is required.

- Private Docker OCR service: Tesseract 5, Pillow and OpenCV. Corrects EXIF orientation, conditionally crops a large document boundary, estimates small skew, enhances contrast, and compares grayscale/thresholded OCR passes. A sparse-text pass is attempted when confidence is low.
- SO-only, rate-limited `POST /office-desk/budget-utilization/receipts/scan`. JPEG/PNG/WebP only, maximum 10 MB and 24 megapixels. PDFs remain clearly labeled **manual entry**; HEIC needs conversion to JPEG.
- Merchant/recipient is separate from payment method (GCash, Maya, cash, card, bank transfer, unknown). Savemore and explicit recipient labels are supported; unfamiliar merchant headers remain low confidence or blank. This is not a trained universal merchant classifier.
- Reads labeled total, transaction date, and OR/invoice/reference. Does not treat cash tendered, change, subtotal, VAT, or an arbitrary final number as the expense total. Conflicting values remain blank. Ambiguous numeric dates require review. Dates are not silently filled with today's date.
- Per-field heuristic confidence and warnings; SO checks/corrects the fields and provides an expense description. Whole-receipt total starts at quantity 1. Any edit unchecks confirmation. Replacing the image clears old values and scan ID; late responses cannot fill the replacement form.
- Repeatable itemized entry: the SO form supports up to 20 expense rows, each with its own receipt upload. The first row uses the gallery/camera capture; added rows use their own file picker. The server records the batch atomically, so a failed row rolls back every row in that submission.
- DOCX preflight: `POST /office-desk/budget-utilization/receipts/validate-document` checks the DOCX archive and visible document text before it is accepted. Activity proposals and schedule/checklist templates are rejected as non-receipts; receipt-like invoice/OR documents continue to a manual field review. This is classification, not authenticity verification.
- Server-issued scan UUID is bound to uploader and exact SHA-256 file bytes for one hour. A photo cannot be posted using a forged, expired, wrong-user, or wrong-file scan. Client-supplied confidence/quality is ignored. PDF manual entries receive no fabricated OCR confidence.
- `receipt_scans` stores raw OCR, extraction scores/results, engine, uploader, file hash and expiration. `expense_receipt_reviews` stores the scan link, type, payment method and original-versus-reviewed corrections. Raw OCR stays in the private database, not the response or blockchain. The Docker service does not retain uploaded originals and makes no third-party OCR calls; it uses temporary files in tmpfs while scanning.
- DOCX preflight requires the PHP `zip` and `dom` extensions. The validator reads the DOCX archive in memory/temporary upload storage and never publishes the extracted text.
- Receipt history shows payment channel and whether the SO corrected the scan. ZIP expense register adds type, channel, server confidence and corrections. Original receipts remain private and downloadable using existing SO/OSO access controls.
- Scanning alone never records an expense or changes a balance. Existing approved-activity checks, duplicate submission handling, one-time deductions and blockchain retry behavior are unchanged.

## Start / verify

Keep Docker Desktop running on the same host as PHP:

```powershell
docker compose -f infra/receipt-ocr/compose.yaml up -d --build
docker compose -f infra/receipt-ocr/compose.yaml ps
Invoke-RestMethod http://127.0.0.1:8091/health
php artisan migrate --path=database/migrations/2026_09_21_000008_add_server_receipt_scans.php --force
php artisan view:cache
```

The service binds **127.0.0.1:8091**, runs non-root/read-only with dropped capabilities, and is separate from Besu. Do not expose its unauthenticated internal `/scan` port publicly. Laravel is the authenticated gateway. `RECEIPT_OCR_URL` can override its location when deployment topology changes; use a controlled private endpoint. The container restarts with Docker unless explicitly stopped. Engine or Docker downtime shows a retry error and does not silently bypass photo scanning.

PHP HTTP timeout is 75 seconds, browser timeout 85 seconds, and individual OCR passes have 18-second limits. Production reverse proxies/PHP workers must allow the bounded request time. The demo `artisan serve` is not a production concurrent worker setup. Dependency upgrades should be tested against the synthetic fixtures before deployment.

Database backup before migration: `storage/app/private/backups/activity-budget-20260921-011949/database.sql`. Existing ledger entries and receipts were preserved. Tests create isolated transaction fixtures and mock blockchain writes; they do not create live expenses or debit real funds.

## Verification

Final run: **96 backend tests passed (704 assertions, 1 expected integration skip), plus 6 scanner-state JavaScript tests**. The batch-recording and DOCX preflight feature tests passed. Local OCR container reported healthy. Blade view cache and JavaScript syntax checks passed.

```powershell
$env:RECEIPT_OCR_INTEGRATION='1'
php artisan test --compact
node --test tests/Unit/receipt-scanner.test.mjs
docker compose -f infra/receipt-ocr/compose.yaml exec -T scanner python smoke_test.py
```

Synthetic fixtures in `tests/Fixtures/receipts/` are visibly marked **SYNTHETIC TEST – NOT VALID**. One is a GCash-style payment to Savemore (PHP 1,234.50); one is a slightly tilted store receipt (PHP 300, cash PHP 500, change PHP 200). Both were processed by the actual local OCR service and then checked through Laravel's scan endpoint. Parser tests cover missing fields, low scores, ambiguous/invalid/future dates, conflicting totals, split labels and dotted reference labels. Integration tests cover authorization, file limits, scan ownership/hash/expiry, corrections, unavailable/unreadable scans, manual PDFs, confirmation, and budget preservation.

UI state unit tests cover slow/stale responses, replacement/reset, correction confirmation, retry, unsupported files, manual PDFs and safe filename display. Chrome desktop and narrow responsive form inspection passed at 360 CSS pixels with 46px controls and no form overflow; no console errors were observed. Browser file-selection automation was blocked by the extension's **Allow access to file URLs** permission. Consequently, a complete interactive upload/camera test on an actual phone is still required; backend image-upload integration tests are not a substitute for that device test.

## Limits and safety

OCR confidence is a heuristic reading-quality hint, **not** a probability of financial correctness, an authenticity check, or a fraud score. A GCash screenshot does not prove settlement or prove what was purchased. A blockchain seal demonstrates record integrity, not that the original receipt was genuine. SO must compare suggestions against the original, and never double-record both a store receipt and its payment screenshot. No automatic item-by-item reconstruction, QR authenticity verification, GCash account lookup, or cross-photo perceptual duplicate detection is claimed.

Expired scan IDs cannot be used for new expenses, but scan records are retained for audit. No destructive automatic retention purge was introduced. Define a private OCR retention policy before wider production use. The existing shared SO account/organization-isolation limitation remains unchanged by this upgrade.

Reference: preprocessing follows the [Tesseract image-quality guidance](https://tesseract-ocr.github.io/tessdoc/ImproveQuality.html); field confidence uses [Tesseract TSV output](https://tesseract-ocr.github.io/tessdoc/Command-Line-Usage.html).
