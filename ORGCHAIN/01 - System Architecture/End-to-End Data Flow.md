---
title: End-to-End Data Flow
tags: [dataflow, sequence, architecture, transactions]
created: 2026-08-20
updated: 2026-10-16
status: active
---

# End-to-End Data Flow

These are current subsystem boundaries. Detailed validation and return/rejection rules belong to the linked workflow notes, not to a historical three-node demonstration sequence.

## Activity approval and visibility

```mermaid
sequenceDiagram
    actor SO
    participant App as Office controllers
    participant DB as MySQL
    actor OSO
    actor SDO
    actor OVCAA
    actor OC
    SO->>App: Confirm and submit activity with original documents
    App->>DB: Persist submitted proposal and private-file metadata
    OSO->>App: Review and endorse
    App->>DB: Record OSO outcome
    SDO->>App: Review and endorse
    App->>DB: Record SDO outcome
    OVCAA->>App: Endorse to OC
    App->>DB: Record OVCAA outcome
    OC->>App: Final decision
    App->>DB: Persist final workflow state
    Note over App,DB: Student queries require oc_approved. Intermediate approval is not final publication
```

Returned/rejected paths are described in [[Activity Proposal Approval Pipeline]].

## Student TOSA packet

```mermaid
sequenceDiagram
    actor Student
    participant UI as Student browser
    participant App as StudentPortalController
    participant Files as Private originals
    participant DB as TosaApplicant
    actor OSO
    Student->>UI: Choose, preview, replace or cancel local files
    Note over UI,App: Choosing files alone does not save them
    Student->>UI: Confirm Submit for Review
    UI->>App: Whole A-G multipart packet
    App->>App: Recheck identity, stage and every required original
    App->>Files: Store the submitted original bytes
    App->>DB: Save one submitted application in Screening
    OSO->>App: Request Revision with review remarks
    App->>DB: Persist returned review stage
    Student->>UI: Refresh, replace requested files, confirm resubmission
    UI->>App: Corrected files with valid saved originals carried over
    App->>DB: Return to Screening and lock again
```

See [[TOSA Application and Review Workflow]]. Evaluator scoring is separate from packet submission, signatures and final award decisions.

## Financial evidence and voting proofs

Receipt metadata/originals, balances and anchor state are separate concerns. Do not call an expense node-confirmed solely because an upload succeeded. Public expense summaries require the implemented review/chain gates; originals remain authorized. See [[Budget Utilization and OCR Receipts]] and [[Expense Review and Financial Liquidation Flow]].

Voting uses the configured driver, eligibility/session checks and persisted ballot/receipt records. The recorded pilot uses Besu; the old three-node JSONL sequence describes only the file-driver mode. Chain verification must distinguish historical proofs, current-network verification, failure and unavailable verification. See [[Blockchain Architecture - Complete]] and [[Receipt Verification and Audit]].

Source: current project README, `StudentPortalController`, `OfficePortalController`, `OrgActivity::visibleToStudents()`, `config/besu.php` and the dedicated workflow services.
