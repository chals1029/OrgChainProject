---
title: Database Schema & ERD
tags:
  - database
  - schema
  - erd
  - sql
  - migrations
created: 2026-08-20
status: active
---

# 🗄️ Database Schema & Entity-Relationship Diagram (ERD)

> [!abstract] Relational Data Model
> The OrgChain database integrates election management, student records, multi-tier proposal submissions, OCR expense auditing, document archiving, and security forensics.

---

## 🗺️ Master Entity-Relationship Diagram

```mermaid
erDiagram
    %% Core Users & Roles
    OFFICE_USERS {
        bigint id PK
        string name
        string email UK
        string username UK
        string password
        string office_role "so | oso | sdo | ovcaa"
        string office_title
        boolean is_active
    }

    STUDENTS {
        bigint id PK
        string sr_code UK
        string name
        string email UK
        string password
        string college
        string program
        string year_level
        boolean is_active
    }

    ADMIN_USERS {
        bigint id PK
        string name
        string email UK
        string password_hash
        string role "admin | canvassing | view_only"
        boolean is_active
    }

    %% Voting System & VoteChain
    ELECTIONS ||--o{ POSITIONS : has
    POSITIONS ||--o{ CANDIDATES : contains
    ELECTIONS ||--o{ VOTES : receives
    VOTERS ||--o{ VOTES : casts
    ELECTIONS ||--o{ VOTE_RECEIPTS : generates
    VOTERS ||--o{ VOTE_RECEIPTS : receives

    ELECTIONS {
        bigint id PK
        string title
        string status "pending | open | closed"
        datetime start_at
        datetime end_at
    }

    POSITIONS {
        bigint id PK
        bigint election_id FK
        string title
        string selection_type "radio | checkbox"
        int max_choices
        int sort_order
    }

    CANDIDATES {
        bigint id PK
        bigint position_id FK
        string name
        string party
        string image_path
        int sort_order
    }

    VOTERS {
        bigint id PK
        string sr_code UK
        string email
        string full_name
        string college
        boolean has_voted
        datetime voted_at
    }

    VOTE_RECEIPTS {
        bigint id PK
        bigint election_id FK
        bigint voter_id FK
        string reference_code UK
        string previous_hash
        string block_hash
        string ballot_root
        string voter_commitment
        tinyint nodes_confirmed
        json node_confirmations
        datetime created_at
    }

    %% Activities & Approvals
    ORG_ACTIVITIES ||--o{ IN_CAMPUS_SUBMISSIONS : tracks
    ORG_ACTIVITIES ||--o{ ACTIVITY_COMPLIANCE_DOCS : reviews
    IN_CAMPUS_SUBMISSIONS ||--o{ ACTIVITY_COMPLIANCE_DOCS : contains
    ORG_ACTIVITIES ||--o{ COMMUNITY_POSTS : references

    ORG_ACTIVITIES {
        bigint id PK
        string title
        text description
        string status "draft | upcoming | ongoing | completed"
        string workflow_status "created | college_review | oso_review | sdo_review | ovcaa_review | oc_approved | returned"
        string activity_scope "in_campus | local_off_campus"
        string organization_name
        string college
        datetime starts_at
        datetime ends_at
        bigint approved_budget
        bigint implemented_budget
        int capacity NULL
    }

    ACTIVITY_REGISTRATIONS {
        bigint id PK
        bigint org_activity_id FK
        bigint student_id
        string sr_code
        string full_name
        string year_level
    }
    ORG_ACTIVITIES ||--o{ ACTIVITY_REGISTRATIONS : rsvps

    IN_CAMPUS_SUBMISSIONS {
        bigint id PK
        bigint org_activity_id FK
        string status "draft | submitted"
        string activity_type "in_campus | local_off_campus"
        string organization_name
        string college
        string workflow_status
        string returned_to
        text rationale
        text objectives
        text participants
        text safety_plan
        text programme_html
        text project_proposal_html
        text budget_proposal_html
        text faculty_in_charge_html
        text medical_request_html
        text insurance_request_html
        text resolution_html
        text sample_letter_html
        text wpcf_html
        text approved_plan_html
        text class_schedule_html
        text meeting_minutes_html
        text off_campus_req_html
        text cert_compliance_html
        text ched_report_html
        text travel_matrix_html
        text passenger_matrix_html
        text course_activities_html
        json attachments
        json document_statuses
        timestamp submitted_at
    }

    ACTIVITY_COMPLIANCE_DOCS {
        bigint id PK
        bigint org_activity_id FK
        bigint submission_id FK
        string doc_key
        string title
        string status "pending | approved | returned"
        string returned_to
        text remarks
    }

    %% Budget & OCR
    BUDGET_ITEMS {
        bigint id PK
        string title
        string category
        bigint allocated
        bigint utilized
        string fiscal_year
    }

    STUDENT_ORGANIZATIONS {
        bigint id PK
        string name UK
        string short_name
        string college
        string academic_year
        boolean is_active
    }

    EXPENSE_RECEIPT_REVIEWS {
        bigint id PK
        string activity_title
        string item_name
        decimal unit_cost
        date expense_date
        string receipt_path
        tinyint ocr_confidence
        boolean student_confirmed
        string verification_status
    }

    %% Social Community
    STUDENTS ||--o{ COMMUNITY_POSTS : authors
    STUDENTS ||--o{ COMMUNITY_COMMENTS : writes
    STUDENTS ||--o{ COMMUNITY_LIKES : toggles
    COMMUNITY_POSTS ||--o{ COMMUNITY_COMMENTS : contains
    COMMUNITY_POSTS ||--o{ COMMUNITY_LIKES : receives

    COMMUNITY_POSTS {
        bigint id PK
        bigint student_id FK
        bigint activity_id FK
        text body
        string image_path
        int likes_count
        int comments_count
    }

    %% Archive Vault
    ORG_REPORT_STATUSES ||--o{ ORG_REPORT_DOCUMENTS : stages
    ORG_REPORT_STATUSES }o--|| ARCHIVE_FOLDERS : archives_to
    ORG_REPORT_STATUSES {
        bigint id PK
        string report_type "ar | fr | budget"
        string organization_name
        string semester
        string academic_year
        string status "draft | oso_review | returned | verified | archived"
        string batch_key NULL
        string returned_to NULL
        datetime submitted_at NULL
        datetime reviewed_at NULL
        bigint reviewed_by NULL
        datetime archived_at NULL
        bigint archive_folder_id NULL
        text notes NULL
    }
    ORG_REPORT_DOCUMENTS {
        bigint id PK
        bigint org_report_status_id FK
        string report_type "ar | fr"
        string organization_name
        string semester
        string academic_year
        string original_name
        string file_path
        string mime_type
        bigint file_size
    }
    ARCHIVE_FOLDERS ||--o{ ARCHIVE_DOCUMENTS : contains
    ARCHIVE_FOLDERS {
        bigint id PK
        string name
        string organization_name
        string semester
        string color
    }
    ARCHIVE_DOCUMENTS {
        bigint id PK
        bigint archive_folder_id FK
        string name
        string file_path
        string mime_type
        bigint file_size
    }
```

---

## 📝 2026-09-19 amendments

- **New table `student_organizations`** (`2026_09_19_000005`) — canonical registry of the 32 OSO-recognized orgs for AY 2025–2026 (`name` unique, `short_name`, `college`, `academic_year`, `is_active`). **No email column by design** — real contact emails from the OSO list are never stored. Drives the Renewal picker, Budget filter, and Activity autocomplete (merged with org names already encoded in `budget_items`).
- `budget_items` also carries `college`, `organization_name`, `supplier`, `is_approved`, `scope` (`in_campus` default).
- `expense_receipt_reviews` also carries `supplier`, `organization_name`, `receipt_reference`, `ocr_quality`, `chain_hash`, `previous_hash`, `nodes_confirmed`.

## 📝 2026-09-20 activity filing amendments

- `in_campus_activity_submissions.activity_type` now distinguishes `in_campus` from `local_off_campus` while reusing the same submission table.
- The existing nullable `*_html` columns remain as legacy-compatible nullable columns; the activity filing UI no longer writes or edits them.
- `attachments` stores uploaded file metadata and selected conditional requirements. Files are stored on the Laravel public disk in an activity-specific folder.
- `activity_compliance_docs` stores one review row per active checklist requirement after final submission.
- The official DOCX templates remain project-folder assets. Preview requests read the original files inline; completed files are stored as upload metadata in `attachments`.

## 📝 2026-09-21 semester reporting amendments

- `org_report_statuses` now carries the paired AR/FR batch lifecycle, OSO reviewer, timestamps, and final archive folder.
- `org_report_documents` stages AR and FR files separately from the archive until OSO accepts the complete semester package.
- AR + FR submission is SO-only; OSO can return or accept the pair; SDO and OVCAA do not mutate semester report status.
