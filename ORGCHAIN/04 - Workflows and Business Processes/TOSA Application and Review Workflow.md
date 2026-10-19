---
title: TOSA Application and Review Workflow
tags: [workflow, student, office, tosa, review]
status: active
---

# TOSA Application and Review Workflow

TOSA is a student-owned, private **whole-packet** application followed by role-authorized office review. Open TOSA from the Student Portal (`/portal#tosa`); office reviewers use `/office-desk/tosa`. There is no separate TOSA PIN and no student score/calculation panel.

## Five references are not seven credentials

The preparation library contains five official reference/template files, each with **View / Download** controls:

1. `Front Page.doc`
2. `Guidelines TOSA 2025.pdf`
3. `TOSA Application Form 2025.docx`
4. `TOSA COMPUTATION.xlsx`
5. `TOSA Submission Guide.doc`

These are **not five mandatory student uploads**. The guide's “Nomination Application Form” is the same `TOSA Application Form 2025.docx`, not an extra form. Complete it with the photo, acknowledgment and candidate/chairperson/dean signatures, then upload the completed document under B. Blank templates, guidelines and the computation workbook are references, not evidence of achievement. The inconsistent 2024/2025 guide dates do not establish a current filing deadline.

Reference DOC files can preview through private source-derived PDF conversion; downloads preserve the original DOC bytes. Reference cards do not show redundant filename/format/source metadata.

## Required A–G packet

| Section | Requirement / upload key | Actual supporting material | Digital format |
| --- | --- | --- | --- |
| A | Table of Contents / `table_of_contents` | Labeled cover and table of contents in guide order | PDF |
| B | Completed TOSA Application Form / `application_form` | Completed nomination/application with required photo, acknowledgment and signatures; not the blank template | PDF or DOCX |
| C | Profile / `cv` | Summary of profile, résumé or curriculum vitae | PDF |
| D | Good Moral Character / `good_moral` | Good moral certificate | PDF |
| E | Academic Evidence / `academic` | Registrar-signed certified grades with GWA, authenticated semestral awards and evidence, certified seminar/workshop and organization-participation documents for claimed achievements | PDF |
| F | Leadership Evidence / `leadership` | Certified, identified and labeled evidence of positions/awards from freshman year to present | PDF |
| G | Social Responsibility Evidence / `social_responsibility` | Certified community/civic activity certificates grouped into school and non-school activities | PDF |

Each file is limited to **25 MB**. These are the platform's digital formats, not a claim that the paper guide itself demands PDFs. Server checks require all seven sections to be readable, nonempty, valid native-format files bound to the student's private `tosa/{sr_code}/` ownership directory. Metadata claiming an upload is insufficient.

## Choose, preview, replace, cancel, submit

1. Use **Choose file** or **Replace file** for each credential. Selection remains local; it does not upload/save a file or create an office-visible draft.
2. Click the selected filename to preview its actual bytes. Its compact file-icon/filename/format-size card sits above **Replace file** and **Cancel selection**. The redundant “Selected · Not submitted” label and “No file saved for this section” sentence are absent.
3. While a replacement is selected, conflicting saved/missing details and the saved original's Download control are hidden. **Cancel selection** clears only the local choice and restores the real saved original/download, or the missing-file state. It does not delete a stored original.
4. All A–G sections must be valid local selections or valid actual saved private originals. Invalid selections must be corrected or cancelled even when an older saved file exists. The disabled submit wrapper is keyboard-focusable and shows a visible hover/focus tooltip identifying missing/invalid sections; once ready, the enabled button is the normal focus target and the tooltip is hidden.
5. Click **Submit for Review once**, then confirm. One multipart `POST /portal/tosa/submit` saves the entire packet and submits it for OSO evaluation. There are **no per-file Save/Delete buttons or mutation endpoints**.
6. Successful submission immediately locks editing/resubmission and sets `status=submitted`, `subsection=screening`. Previously saved originals may still be previewed/downloaded privately.

The student page uses compact full-width collapsible sections with consistent small gutters. The application-form card and preparation library start expanded; Revision opens status/remarks and credentials automatically. Validation reveals the credential area. Read-only previews/downloads and local choice/replacement/cancellation do not prompt; cancelling the final confirmation preserves the current files/draft without posting.

## Revision is the only submitted-packet unlock

Office stage values are **Pending, Screening, Interview, Accepted, Rejected and Revision** (`pending`, `screening`, `interview`, `accepted`, `rejected`, `returned`). A submitted application remains locked unless its stage is **Revision / `returned`**. Acceptance stays locked; rejection is **not** a revision unlock.

```text
Prepare A–G locally --> confirm whole packet --> Submitted / Screening (locked)
                                                     |
                                          authorized review / remarks
                                                     |
                           Return / Revision --------+------ Accepted or Rejected (locked)
                                 |
                   select replacements / keep valid originals
                                 |
                   confirm one whole-packet resubmission
                                 |
                           Screening (locked again)
```

The server rechecks persisted ownership, lock state and all originals under row locks. Failed packet storage does not replace the previous committed application. A resubmission with changed evidence clears stored GWA verification/remarks/evaluation and needs fresh review; a Return/Reject or changed reviewed grades/verification makes a retained calculation provisional.

## Office review and evaluator scoring

- **Applicants** is the default office tab, with search, stage/program/year filters, pagination and a filtered roster export. **Requirements** distinguishes A–G from the five references; **Top10** is academic-year-scoped.
- Drafts and never-submitted applications are excluded from lists, counts, exports and direct dossier/review access.
- OSO has Level 3 Master clearance. OVCAA defaults to Level 1 read-only; Level 2 Evaluator can review/score. Its configured clearance is capped at Level 2, so it cannot gain OSO-only reference management. SO, SDO and OC have no TOSA clearance.
- Authorized evaluators enter and verify GWA and evidence-referenced achievements. Upload-format/readiness checks do **not** verify signatures, extract GWA, certify achievements or auto-score DOCX content.
- Server scoring caps the total at **100 points**: grades 30, academic awards 10, leadership 30 and social responsibility 30, with criterion/category caps and integer milli-points.
- **Save provisional** is not a verified result. **Finalize verified score** requires verified GWA 1.00–3.00, verified eligibility, a consecutive academic year and all seven originals. Scoring does not accept the application or award TOSA automatically.
- Ranking includes only accepted applications with finalized, verified eligible evaluations and matching reviewed GWA in the same academic year. Equal totals share competition ranks; ties at rank 10 remain visible and a tie across the ten-place cutoff requires committee resolution, not an arbitrary GWA/name tiebreak.
- The student personal score/calculation panel and its ranking queries were removed. Protected evaluator scoring and personal/reference workbook preview/download endpoints remain; their existence does not mean that panel is displayed. A personal workbook requires a saved evaluation; otherwise its endpoint returns 404.
- Reference replacement is OSO-only and staged until explicit confirmed upload; simply selecting a template does not replace it.

## Current routes

| Method | Route | Boundary |
| --- | --- | --- |
| GET | `/portal/tosa/templates/{key}` | Student reference; `preview=1` previews, otherwise downloads the original |
| GET | `/portal/tosa/documents/{key}` | Student's own saved original preview/download |
| POST | `/portal/tosa/submit` | One complete packet; no per-file mutation route |
| GET | `/portal/tosa/workbook/reference` | Authenticated reference workbook |
| GET | `/portal/tosa/workbook`, `/portal/tosa/workbook/download` | Protected own workbook preview/XLSX download, not a restored score panel |
| GET | `/office-desk/tosa` | TOSA-clearance-authorized desk |
| GET | `/office-desk/tosa/{applicant}/documents/{key}` | Authorized submitted dossier original |
| POST | `/office-desk/tosa/{applicant}/subsection` | Level 2+ review stage / GWA / remarks |
| POST | `/office-desk/tosa/{applicant}/scoring` | Level 2+ evaluator scoring |
| POST | `/office-desk/tosa/references/template` | OSO Master reference replacement |
| GET | `/office-desk/tosa/workbook/reference` | Office reference workbook |
| GET | `/office-desk/tosa/{applicant}/workbook`, `/office-desk/tosa/{applicant}/workbook/download` | Authorized applicant workbook preview/download |

Related: [[Student Profile and Authentication]] · [[Transparency and Budget Visibility]] · [[Multi-Tier Office Roles SO OSO SDO OVCAA]] · [[Web and Portal Route Map]] · [[Office Portal API and Action Endpoints]]

## Source evidence and boundary

`app/Services/TosaRequirements.php`; `app/Models/TosaApplicant.php`; `app/Models/OfficeUser.php`; `StudentPortalController::submitTosaApplication()`; `OfficePortalController::tosa()` / `updateTosaSubsection()`; `TosaScoringController` / `TosaScoringService`; `public/js/tosa-documents.js`; `resources/views/portal/index.blade.php`; `routes/web.php`; current README/CHANGELOG. This is a source-based workflow description, not a claim of a new local or production submission test.
