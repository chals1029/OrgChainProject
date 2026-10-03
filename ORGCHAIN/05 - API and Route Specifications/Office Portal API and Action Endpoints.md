---
title: Office Portal API & Action Endpoints
tags: [api, office, actions, endpoints]
created: 2026-08-20
---

# 🏢 Office Portal API & Action Endpoints

- `POST /office-desk/activities`: Creates a new in-campus or off-campus submission draft.
- `PUT /office-desk/activities/{id}`: Updates activity rationale, schedule, and uploaded checklist files.
- `GET /office-desk/activities/templates/download?type=in_campus|local_off_campus`: Downloads the official template pack, or one safe template file when `file` is provided.
- `GET /office-desk/activities/templates/download?type=in_campus|local_off_campus&file={name}&preview=1`: Serves one official template inline for the self-hosted browser preview; it does not trigger a download response.
- The activity form does not expose an in-app editor or generated document export. Official templates are completed externally and submitted as checklist uploads.
- `POST /office-desk/activities` and `PUT /office-desk/activities/{id}` accept `submission_action=draft|submit`, selected `activity_type`, conditional requirement flags, keyed `attachments[...]`, and bulk `supporting_documents[]` uploads.
- Final submission creates or updates `activity_compliance_docs` rows for active checklist requirements.
- `POST /office-desk/budget-utilization/receipts/scan`: SO-only image OCR preflight; returns a short-lived scan UUID bound to the uploader and exact file hash.
- `POST /office-desk/budget-utilization/receipts/validate-document`: SO-only DOCX preflight; checks the DOCX archive and visible text for receipt-like content, rejecting activity/proposal templates before final submission.
- `POST /office-desk/budget-utilization/receipt-reviews`: SO-only itemized receipt submission. Accepts one to 20 `expenses[]` rows, each with one receipt file. The server revalidates every row, writes the batch atomically, updates activity and organization balances, and seals each receipt through the permissioned validator nodes. OSO/SDO/OVCAA/OC do not manually approve receipt rows.
- `GET /office-desk/budget-utilization/receipts/{review}/view`: Serves a sealed receipt file for viewing (`office.budget.receipts.view`).
- `GET /office-desk/analytics/export`: Downloads the live Analytics report as CSV (`office.analytics.export`).
- `POST /office-desk/archive/folders`: Creates a new semester folder in the compliance vault.
- `POST /office-desk/archive/documents`: Uploads files (`pdf`, `docx`, `xlsx`) into a designated archive folder.
- `POST /office-desk/reports/{ar|fr}/documents`: SO-only staging upload for one semester AR or FR document; staged files are not in Archive yet.
- `POST /office-desk/reports/semester/submit`: SO-only submission of the paired AR + FR package to OSO. Both report types must have at least one staged document.
- `POST /office-desk/reports/semester/review`: OSO-only return or accept action. Acceptance creates the archive folder and moves both report documents into it; SDO, OVCAA, and OC cannot mutate this workflow.
- `GET /office-desk/reports/documents/{document}/view`: SO/OSO-only preview/download of a staged AR or FR document; SDO, OVCAA, and OC are denied at the controller boundary.
