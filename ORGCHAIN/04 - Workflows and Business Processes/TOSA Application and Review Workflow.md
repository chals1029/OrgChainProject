---
title: TOSA Application and Review Workflow
tags: [workflow, student, office, tosa, review]
status: active
---

# TOSA Application and Review Workflow

OSO announces TOSA, students submit the required documents, and OSO manually verifies them. Open TOSA in the Student Portal (`/portal#tosa`) or office desk (`/office-desk/tosa`).

## Five references are not seven credentials

The preparation library contains five official references available to download:

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
| B | Completed TOSA Application Form / `application_form` | Completed nomination/application with required photo, acknowledgment and signatures; not the blank template | DOCX |
| C | Profile / `cv` | Summary of profile, résumé or curriculum vitae | PDF |
| D | Good Moral Character / `good_moral` | Good moral certificate | PDF |
| E | Academic Evidence / `academic` | Registrar-signed certified grades with GWA, authenticated semestral awards and evidence, certified seminar/workshop and organization-participation documents for claimed achievements | PDF |
| F | Leadership Evidence / `leadership` | Certified, identified and labeled evidence of positions/awards from freshman year to present | PDF |
| G | Social Responsibility Evidence / `social_responsibility` | Certified community/civic activity certificates grouped into school and non-school activities | PDF |

Each file is limited to **25 MB**. These are the platform's digital formats, not a claim that the paper guide itself demands PDFs. Server checks require all seven sections to be readable, nonempty, valid native-format files bound to the student's private `tosa/{sr_code}/` ownership directory. The application form must be DOCX and cannot be the unchanged official template; OSO must still check its answers and signatures. Metadata claiming an upload is insufficient.

## Choose, preview, replace, cancel, submit

OSO opens new submissions by publishing a **Call for TOSA** with its requirements and instructions. The call closes when its announcement expires. A submitted packet returned for Revision may be resubmitted after the call closes.

1. Use **Choose file** or **Replace file** for each credential. Selection remains local; it does not upload/save a file or create an office-visible draft.
2. Click the selected filename to preview its actual bytes. Its compact file-icon/filename/format-size card sits above **Replace file** and **Cancel selection**. The redundant “Selected · Not submitted” label and “No file saved for this section” sentence are absent.
3. While a replacement is selected, conflicting saved/missing details and the saved original's Download control are hidden. **Cancel selection** clears only the local choice and restores the real saved original/download, or the missing-file state. It does not delete a stored original.
4. All A–G sections must be valid local selections or valid actual saved private originals. Invalid selections must be corrected or cancelled even when an older saved file exists. The disabled submit wrapper is keyboard-focusable and shows a visible hover/focus tooltip identifying missing/invalid sections; once ready, the enabled button is the normal focus target and the tooltip is hidden.
5. Click **Submit for Review** once. One multipart `POST /portal/tosa/submit` saves the complete packet for OSO review.
6. Successful submission immediately locks editing/resubmission and sets `status=submitted`, `subsection=screening`. Previously saved originals may still be previewed/downloaded privately.

Submitted files stay available for private viewing or download. If OSO returns the packet, the student can correct and resubmit it.

## Manual document review

- OSO opens each submitted file and checks the documents manually.
- After reviewing all A–G files, OSO selects **Add to Top 10** and confirms once. The action records the review and adds the applicant to the private draft; missing originals block it.
- OSO selects a document and enters a reason to return the packet for correction. The reason appears in the student's TOSA status and an email is sent to the uniquely matched active student account. If the address is missing, ambiguous or sending fails, the return is saved and OSO sees a warning. Resubmission clears the previous verification.
- OrgChain does not assign evaluators, extract or request GWA, calculate scores, or rank applicants. Checking upload format does not certify the contents.
- Existing applications, files and historical review data are preserved.

## Office workspace

- **Submissions:** search submitted applicants in a scrollable box with ten records per page; view their files, add a reviewed applicant to the Top 10 draft, return a selected document with a reason, or download one applicant's originals as a private ZIP. Missing originals cannot be reconstructed and must be uploaded by the student. The ZIP identifies unavailable legacy files in `unavailable-documents.txt`; no ZIP is generated if every original is unavailable.
- **Documents & Announcements:** file-type, view, download and replace icons provide access to the five references, including `TOSA Application Form 2025.docx`. Replacing a reference still requires choosing a file and explicitly saving it. OSO can also publish a Call for TOSA.
- **Top 10:** OSO manages up to ten reviewed applicants without grades or scores. Uncheck and save to remove someone. The draft stays private; publishing requires exactly ten and shows students names and programs only. Draft edits do not change a published list until it is published again. OSO can unpublish it.
- **History:** records actual submissions, resubmissions, reviews, returns, calls and Top 10 actions from this update onward. Earlier events and submission dates are not fabricated.

OVCAA remains read-only; OSO alone may download the applicant ZIP, confirm reviews, manage references and calls, or save/publish the Top 10.


## Current routes

| Method | Route | Boundary |
| --- | --- | --- |
| GET | `/portal/tosa/templates/{key}` | Student reference; `preview=1` previews, otherwise downloads the original |
| GET | `/portal/tosa/documents/{key}` | Student's own saved original preview/download |
| POST | `/portal/tosa/submit` | One complete packet; no per-file mutation route |
| POST | `/office-desk/tosa/{applicant}/subsection` | OSO returns a selected document with a reason; email outcome is reported |
| GET | `/office-desk/tosa` | TOSA-clearance-authorized desk |
| GET | `/office-desk/tosa/{applicant}/documents/{key}` | Authorized submitted dossier original |
| POST | `/office-desk/tosa/{applicant}/top-ten` | OSO confirms review of all seven current files and adds the applicant to the private draft |
| GET | `/office-desk/tosa/{applicant}/package` | OSO private ZIP of available originals, with missing-file notice |
| POST | `/office-desk/tosa/top-ten` | OSO saves, publishes or unpublishes a manual Top 10 |
| POST | `/office-desk/tosa/references/template` | OSO reference replacement |

Related: [[Student Profile and Authentication]] · [[Transparency and Budget Visibility]] · [[Multi-Tier Office Roles SO OSO SDO OVCAA]] · [[Web and Portal Route Map]] · [[Office Portal API and Action Endpoints]]

## Source evidence and boundary

`app/Services/TosaRequirements.php`; `app/Services/TosaWorkflowService.php`; `app/Models/TosaApplicant.php`; `app/Models/TosaHistoryEvent.php`; `app/Models/TosaShortlist.php`; `StudentPortalController::submitTosaApplication()`; `TosaPackageController`; `TosaShortlistController`; `public/js/tosa-documents.js`; `resources/views/portal/index.blade.php`; `routes/web.php`.
