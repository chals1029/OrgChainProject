# Semester AR and FR Submission Workflow

## Purpose

The Accomplishment Report (AR) and Financial Report (FR) are treated as one end-of-semester package. The Student Organization (SO) prepares both reports, submits them together to the Office of Student Organizations (OSO), and the OSO makes the final report decision.

SDO and OVCAA do not review or approve AR/FR packages. Their activity-proposal responsibilities remain separate from the post-activity semester report workflow.

## Workflow

```text
SO stages AR document
SO stages FR document
        |
        v
SO submits combined AR + FR package
        |
        v
OSO Review
   |             |
   | return      | accept
   v             v
SO revision   Archive AR + FR
   |
   +--> resubmit to OSO
```

## Server-side rules

- A package is identified by organization, semester, and academic year.
- The SO must stage at least one AR document and one FR document before submission.
- Submission sets both report rows to `oso_review` and gives them the same batch key.
- Only an SO can upload report documents or submit the pair.
- Only an OSO account can return or accept the pair.
- Only SO and OSO desks can open the Financial Report, Accomplishment Report, or staged AR/FR document endpoints. SDO and OVCAA dashboards do not expose semester-report metrics or report navigation.
- Returning a package sets both rows to `returned` with `returned_to = so` and stores the OSO note.
- Accepting a package creates the semester archive folder and copies both staged document records into `archive_documents` in one database transaction.
- Acceptance ends in `archived`; SDO and OVCAA have no AR/FR mutation endpoint.

## Persistence

- `org_report_statuses` stores one AR row and one FR row, including the shared `batch_key`, review timestamps, OSO reviewer, and archive folder.
- `org_report_documents` stores staged files until OSO accepts the package.
- `archive_folders` and `archive_documents` are written only during OSO acceptance for this workflow.

## Routes

- `POST /office-desk/reports/ar/documents`
- `POST /office-desk/reports/fr/documents`
- `POST /office-desk/reports/semester/submit`
- `POST /office-desk/reports/semester/review`
- `GET /office-desk/reports/documents/{document}/view`

The Financial Report and Accomplishment Report tabs render the same package state. The OSO review queue is available from either tab, while the Archive page contains the completed package after acceptance.
