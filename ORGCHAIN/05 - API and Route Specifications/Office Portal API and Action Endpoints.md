---
title: Office Portal API & Action Endpoints
tags: [api, office, actions, endpoints]
created: 2026-08-20
---

# Office Portal API and Action Endpoints

These routes are declared inside `office.auth` in `routes/web.php`; controllers enforce additional role, organization, clearance and workflow-stage boundaries. A route's presence is not permission to call it.

## Activities and receipt records

- `POST /office-desk/activities`, `PUT /office-desk/activities/{submission}`: SO saves/submits structured in-campus or local off-campus proposals. `submission_action=draft|submit`, the selected approved Attachment I activity and keyed checklist uploads determine the packet. Title/reference derive from the selected organization-bound approved-plan entry; ₱0 is permitted. See [[Activity Proposal Approval Pipeline]].
- `GET /office-desk/activities/templates/download?type=in_campus|local_off_campus`: downloads the template pack; `file={name}` selects one safe file and `preview=1` serves its preview instead. Templates are completed externally, not in an in-app document editor.
- `POST /office-desk/documents/preview/convert`: authorized active office accounts privately convert genuine legacy DOC bytes for preview; this does not approve or submit a document.
- `POST /office-desk/budget-utilization/receipt-reviews`: SO records up to 20 manual item rows (`expenses[]`) with one shared `receipts[]` set of up to three image/PDF files. The server revalidates, stores atomically, posts quantity × unit-cost cash deductions once and seals receipts. Office reviewers do not manually approve receipt rows.
- `POST /office-desk/budget-utilization/receipts/validate-document`: throttled SO-only DOCX preflight retained for the older compatibility path; it is not OCR or the active manual photo-entry step.
- `POST /office-desk/budget-utilization/receipts/{review}/retry`: SO retries a pending seal, not another debit.
- `GET /office-desk/budget-utilization/receipts/{review}/view`: SO/OSO-only, organization-checked original viewing; `attachment={index}` selects a shared file, `download=1` downloads original bytes.
- `GET /office-desk/analytics/export`: authorized live Analytics CSV.

See [[Budget Utilization and OCR Receipts|Budget utilization and receipt records]] and [[Transparency and Budget Visibility]]. Feedback acknowledgment terminology does not rename receipt seal verification.

## Independent semester reports

| Method | Route | Boundary / behavior |
| --- | --- | --- |
| GET | `/office-desk/reports` | OSO combined directory, with independent AR/FR states |
| POST | `/office-desk/reports/{reportType}/documents` | SO stages a private AR/FR draft for its organization |
| POST | `/office-desk/reports/{reportType}/submit` | SO submits only `ar` or `fr`; current readable file/window gates apply |
| POST | `/office-desk/reports/{reportType}/review` | OSO `accept`, `return` or `reject` of only that report |
| POST | `/office-desk/reports/{reportType}/submission-lock` | OSO locks/reopens one type/year/semester for all organizations |
| GET | `/office-desk/reports/documents/{document}/view` | SO/OSO ownership/publication-checked original; `download=1` downloads |
| GET | `/office-desk/reports/documents/{document}/workbook` | OSO workbook preview |
| POST | `/office-desk/reports/{report}/status` | OSO legacy status update; explicitly rejects AR/FR, which must use report-specific review |
| POST | `/office-desk/financial-report/reports` | SO creates a report from completed official XLSX |
| GET | `/office-desk/financial-report/reports/{document}/preview` | SO exact-file preview gate |
| POST | `/office-desk/financial-report/reports/{document}/submit` | SO submits the current previewed FR |
| POST | `/office-desk/accomplishment-report/activities` | SO native activity-report preparation |
| POST | `/office-desk/accomplishment-report/activities/{report}` | SO updates an editable native activity report |

AR and FR are **not** a required pair; former `/reports/semester/submit` and `/reports/semester/review` routes do not exist in the current map. Each submission locks that report immediately. Only return for revision reopens it; rejection does not. OSO acceptance verifies and archives that report's current submitted document on the private disk. SDO/OVCAA/OC cannot mutate or view semester-report pages/files.

Locking has a dedicated report/period/**all-organizations** dialog; only **Yes, lock submissions** posts. Reopen confirms separately. Filing locks block new submissions/resubmissions, not draft preparation, previews/downloads or OSO review of existing submissions, and do not unlock a report already under review.

See [[Semester AR and FR Submission Workflow]].

## Announcements, feedback and explicit settings saves

| Method | Route | Boundary / behavior |
| --- | --- | --- |
| GET | `/office-desk/updates?announcement={id}` | Open the requested persisted notice |
| POST | `/office-desk/updates/announcements` | OSO publishes announcement metadata/body/optional attachment |
| POST | `/office-desk/updates/announcements/{announcement}/acknowledge` | Active office actor's idempotent timestamped notice receipt |
| GET | `/office-desk/updates/announcements/{announcement}/attachment` | Authenticated attachment download |
| GET | `/office-desk/updates/announcements/{id}/document` | Authenticated announcement-document access |
| POST | `/office-desk/updates/templates` | OSO explicit template save, not selection-time upload |
| GET | `/office-desk/updates/templates/{template}/download` | Template download |
| GET | `/office-desk/updates/templates/{id}/document` | Template/document preview access |
| GET | `/office-desk/student-reports` | OSO feedback queue |
| POST | `/office-desk/student-reports/{report}/review` | OSO `decision=acknowledged|dismissed`; preserves first decision |
| POST | `/office-desk/settings` | Explicit authorized settings save |
| POST / DELETE | `/office-desk/settings/logo` | Explicit logo upload/reset with role authorization |

**Acknowledge report** means OSO acknowledged student feedback, not certified it as true. Historical `status=verified`, `verified_by` and `verified_at` are retained internally; notification/history is scoped to the submitting student. Dismissal is not acknowledgment. AR/FR verification, receipt seals and evaluator verification keep their original meanings.

**Acknowledge Notice** is a separate, office-account-scoped receipt; repeat calls retain the first timestamp. **Copy Link** copies the real `/office-desk/updates?announcement={id}` URL. The office page requires the additive announcement-acknowledgment migration; the existing local structure-only ERD does not yet contain that VPS-deployed table. No migration is performed by this documentation update.

Business submissions, reviews, deletions and settings changes use shared confirmation without duplicating dedicated lock/logout dialogs. Cancelling preserves draft/selected-file state and sends no business mutation. Navigation, previews/downloads and file selection/replacement/cancellation are unprompted. Logo and template choices remain staged until explicit confirmed upload/save.

See [[Transparency and Budget Visibility]] and [[Multi-Tier Office Roles SO OSO SDO OVCAA]].

## TOSA review

| Method | Route | Boundary / behavior |
| --- | --- | --- |
| GET | `/office-desk/tosa` | Submitted applicants, references/calls, manual Top 10 and history |
| GET | `/office-desk/tosa/{applicant}/documents/{key}` | Authorized submitted applicant's original |
| POST | `/office-desk/tosa/{applicant}/subsection` | OSO returns a selected document with a reason; email failure is reported |
| POST | `/office-desk/tosa/{applicant}/top-ten` | OSO confirms all seven current files and adds the applicant to the private draft |
| GET | `/office-desk/tosa/{applicant}/package` | OSO-only private ZIP; unavailable legacy originals are listed |
| POST | `/office-desk/tosa/top-ten` | OSO saves, publishes or unpublishes an unranked list of ten reviewed applicants |
| POST | `/office-desk/tosa/references/template` | OSO Master-only explicit reference replacement |

No PIN is required; role/clearance checks remain. OSO verifies documents and explicitly publishes the manually chosen list; authorized OVCAA access is read-only. Submitted packets unlock only when returned for correction. There is no evaluator assignment, GWA input or automatic ranking. History records events only from this update onward. See [[TOSA Application and Review Workflow]].

## Renewal and Archive

- `POST /office-desk/renewal/information` saves SO adviser/dean names only; it neither uploads selected files nor submits the renewal. `POST /office-desk/renewal/submit` uploads/submits the complete packet and locks it. OSO manages `/renewal/window` and `/renewal/submissions/{submission}/review`; no per-file `/renewal/documents` upload mutation is current. See [[Organization Renewal Filing Window]].
- `POST /office-desk/archive/folders` and `/office-desk/archive/documents` are SO/OSO actions with organization/parent-folder access checks. Explicit **Upload & Archive** stores new uploads privately on `local`, up to 20 MB, with DOCX validation. File selection alone does not save or submit AR/FR.
- `GET /office-desk/archive/documents/{document}/view` and `/download`, plus `/archive/activity-documents/{document}/view` and `/download`, preserve authenticated access and original downloads. See [[Document Archive and Compliance Repository]].

## Source evidence

`routes/web.php`; controller/service guards cited in the linked workflows; `public/js/action-confirmations.js`; portal/office views; current README/CHANGELOG. These notes describe source behavior, not a newly exercised endpoint test. Full navigation: [[Web and Portal Route Map]].
