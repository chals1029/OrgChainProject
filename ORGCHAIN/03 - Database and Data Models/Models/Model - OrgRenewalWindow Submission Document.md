---
title: Model - OrgRenewalWindow Submission Document
tags:
  - models
  - renewal
  - eloquent
created: 2026-09-18
---

# Models — Organization Renewal

Connection: `mysql` (default app DB).

## `OrgRenewalWindow`

| Column | Notes |
| :--- | :--- |
| `academic_year` | e.g. `2026-2027` |
| `semester` | 1st / 2nd / Midyear / Annual |
| `is_open` | bool — master switch |
| `opens_at` / `closes_at` | optional schedule bounds |
| `instructions` | shown on SO desk |
| `required_docs` | JSON list of `{key, title}` |
| `opened_by` / `closed_by` | office user ids |
| `notes` | OSO internal |

Helpers: `defaultRequiredDocs()`, `requiredDocList()`, `isAcceptingSubmissions()`.

## `OrgRenewalSubmission`

| Column | Notes |
| :--- | :--- |
| `renewal_window_id` | FK |
| `organization_name` | unique per window |
| `college` | optional |
| `submitted_by` | SO office user id |
| `adviser_name` / `dean_name` | text (roles not in auth yet) |
| `status` | `draft` \| `submitted` (+ future review states) |
| `notes` | |
| `submitted_at` | |

Helpers: `uploadedKeys()`, `completionPercent()`.

## `OrgRenewalDocument`

| Column | Notes |
| :--- | :--- |
| `submission_id` | FK |
| `doc_key` | matches required doc key |
| `title` | display title |
| `file_path` | `storage/app/public/renewal-documents/...` |
| `file_name` | original name |

Unique: `(submission_id, doc_key)`.

## Related

- [[Organization Renewal Filing Window]]
- [[Database Schema and ERD]]
