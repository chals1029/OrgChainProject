---
title: Session Log 2026 09 21 Official Templates
tags:
  - operations
  - templates
  - documents
  - oso
created: 2026-09-21
status: active
---

# Official Template Source Update

## Outcome

The OSO Official Template Documents page now serves the supplied university files instead of the old hard-coded demo cards and generated text/DOCX substitutes.

## Source-backed catalogue

- In-campus checklist, programme, project proposal, budget proposal, and activity request letter from `In Campus/`.
- Waste Policy Compliance Form from the supplied legacy `.doc` file. Its original `.doc` remains the download source; the existing PDF render is used for browser preview.
- Local off-campus request and compliance checklist from `Local Off Campus/`.
- TOSA Application Form 2025 and TOSA Computation Workbook from `TOSA/`.
- Accomplishment & Financial Report, Particulars of the Accomplishments, and Written Explanation from `Reports/`.

The controller omits a catalogue entry when its source file is absent, calculates the displayed format and size from the actual file, and routes preview requests inline without changing the original download response. Missing sources are not replaced by generated sample documents.

## Verification

- `php artisan test --compact tests/Feature/OfficialTemplateDocumentsTest.php` — 3 tests passed, 28 assertions.
- `php artisan view:cache` — passed.
- Each selected DOCX/XLSX source was checked as a valid OOXML ZIP with `[Content_Types].xml` and a stable SHA-256 hash before wiring the routes.
- The bundled document renderer could not run because the workspace dependency bundle does not include `soffice.exe`; no source DOCX files were edited or repackaged.
