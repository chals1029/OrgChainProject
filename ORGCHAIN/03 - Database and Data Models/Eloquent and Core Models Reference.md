---
title: Eloquent & Core Models Reference
tags:
  - models
  - eloquent
  - pdo
  - database
created: 2026-08-20
updated: 2026-10-16
status: active
---

# Eloquent & Core Models Reference

OrgChain has two model layers: Laravel Eloquent models under `app/Models/` for the portal/office workflows, and native PDO-backed models under `app/VotingSystem/Models/` for elections. A table name does not guarantee a same-named model, and an Eloquent relation does not guarantee a physical foreign key.

Related: [[Database Schema and ERD]] · [[Application Relationships and Schema Boundaries]]

> [!important] Source and schema are different evidence
> This reference describes current checked-in source. The linked local ERD is a **2026-10-15 structure-only snapshot**, not production schema. It lacks `office_announcement_acknowledgments` and the `tosa_applicants.score_evaluation` column used by current source. No model cast or migration file proves that a particular database has been migrated.

## Identity, connections and office roles

| Class | Storage / identity | Current meaning |
| --- | --- | --- |
| `App\Models\UserAccount` | Explicit `orgchain` connection, `user_accounts` table, primary key `user_id`; timestamps disabled | **Current student guard identity**. `id` aliases `user_id`, `name` derives from stored identity fields, `getAuthPassword()` uses `password_hash`, and `isActive()` checks `account_status` |
| `App\Models\Student` | Conventional `students` table on the inherited/default connection; standard `id` | Legacy student model, **not** the configured student provider. Existing `posts/comments/likes` methods must not be used as evidence that current community authors are `students.id` |
| `App\Models\OfficeUser` | `office_users`, separate `office` guard; belongs to `StudentOrganization` through `student_organization_id` | SO/office workflow account with hashed password, active-state and credential/session-version controls |
| `App\Models\SystemAdminUser` | `system_admin_users`, separate `system_admin` guard | System administration account; not the voting administrator table |
| `App\VotingSystem\Models\AdminUser` | Native PDO access to `admin_users` | Voting staff identity with `admin`, `canvassing`, `view_only` roles; not an Eloquent alias for `OfficeUser` |
| `App\Models\User` | Conventional Laravel `users` model; `web` provider defaults to this class | Distinct from the student, office and system-admin providers |

`config/auth.php` explicitly maps the student provider to `UserAccount::class`. `config/database.php` defines distinct configurable `mysql` and `orgchain` connections; models without an explicit connection inherit the default. In the attached local snapshot, `mysql`-side domain tables are described under `votingsystem`. Do not assume the two exported `student_organizations` tables are interchangeable: the office registry uses `id`/`name`, while the older `orgchain` table uses `org_id`/`org_name`.

### All office roles, including OC

`OfficeUser::roleLabels()` defines `so` (Student Organization), `oso` (Office of Student Organization), `sdo` (Sustainable Development Office), `ovcaa` and **`oc` (Office of the Chancellor)**. OC is part of final proposal review; it is not missing merely because an older diagram ended at OVCAA. Role strings describe authorization identities, not separate tables or model classes.

TOSA clearance is a separate rule in `OfficeUser::tosaClearanceLevel()`:

- OSO receives level 3 (master).
- OVCAA receives level 1 by default; evaluator clearance is level 2, and a stored master label is capped at level 2.
- SO, SDO and OC receive no TOSA clearance from this method.
- Scoring writes additionally require an active OSO/OVCAA account with clearance at least 2. Do not equate being a proposal reviewer with being a TOSA evaluator.

Evidence: `config/auth.php`; `app/Models/UserAccount.php`, `Student.php`, `OfficeUser.php`; `app/Http/Controllers/TosaScoringController.php`.

## Core model directory

The older linked model notes remain available by their existing names. For current identity/storage behavior, use this reference, the schema guide and the cited source rather than treating an older note as a live-schema guarantee.

| Area | Actual models / implementation | Related existing note |
| --- | --- | --- |
| Office and voting staff | Eloquent `OfficeUser`, `SystemAdminUser`; native PDO `AdminUser` | [[Model - AdminUser and OfficeUser]] |
| Student identity | Eloquent `UserAccount`; legacy `Student` | [[Model - Student and UserAccount]] |
| Ballot structure | Native PDO `Election`, `Position`, `Candidate` | [[Model - Election Position Candidate]] |
| Ballots and receipts | Native PDO `Vote`, `Voter`; `Vote::submit()` inserts into `vote_receipts` | [[Model - Vote and VoteReceipt]] |
| Activity filing | Eloquent `OrgActivity`, `InCampusActivitySubmission`, `ActivityComplianceDoc`, `ActivityRegistration` | [[Model - InCampusActivitySubmission and OrgActivity]] |
| Finance | Eloquent `BudgetItem`, `ExpenseReceiptReview`, `ReceiptScan`, `OrgFundAccount`, `OrgFundSource`, `OrgCashIncome` | [[Model - BudgetItem and ExpenseReceiptReview]] |
| Renewal | Eloquent `OrgRenewalWindow`, `OrgRenewalSubmission`, `OrgRenewalDocument`, `StudentOrganization` | [[Model - OrgRenewalWindow Submission Document]] |
| Community | Eloquent `CommunityPost`, `CommunityComment`, `CommunityLike`, `CommunityCommentLike`, all explicitly on `orgchain` | [[Model - CommunityPost Comment Like]]; [[Application Relationships and Schema Boundaries#Community is not the legacy students graph|current community relationships]] |
| Reports and accomplishments | Eloquent `OrgReportStatus`, `OrgReportDocument`, `OrgReportSubmissionWindow`, `OrgActivityAccomplishment` | [[Application Relationships and Schema Boundaries#Activity accomplishments and semester packets|report associations]] |
| TOSA | Eloquent `TosaApplicant`; behavior in `TosaRequirements`, `TosaScoringService`, `TosaWorkbookService` | See packet/scoring section below; services are not extra models |
| Feedback and notices | Eloquent `StudentFeedback`, `OfficeAnnouncement`, `OfficeAnnouncementAcknowledgment` | [[Application Relationships and Schema Boundaries#Feedback and activity registration use current student identity|feedback identity]]; acknowledgment table absent from local export |
| Archive | Eloquent `ArchiveFolder`, `ArchiveDocument` | [[Model - ArchiveFolder and ArchiveDocument]] |
| Configuration | Eloquent `OfficeSetting`, `OfficeTemplate` | [[Database Schema and ERD#Isolated tables are not automatically missing relationships|standalone configuration tables]] |
| Security/audit | Native PDO `AuditLog`, `SecurityEvent`; Eloquent `SystemAdminAuditLog` | [[Model - AuditLog and SecurityEvent]] |

There is **no standalone `VoteReceipt` model** in the current `app/VotingSystem/Models/` or `app/Models/` directory. The existing note's name is preserved for navigation; the receipt table and its persistence through `Vote` must not be turned into an invented class.

## Files are metadata, not public URLs

Paths, filenames, sizes, hashes, MIME types and JSON attachment entries describe stored files. They are not public access grants and are not file contents in the ERD. This vault contains schema-only attachments, **not private uploads or record values**.

- **Activity submissions:** `InCampusActivitySubmission.attachments` is JSON metadata; the current controller stores completed files on `local` under activity-specific directories. Authenticated attachment routes read those private originals. Old nullable HTML fields do not describe the current upload workflow.
- **Renewal:** `OrgRenewalDocument` records private submitted-document metadata; `OfficePortalController` saves submissions on `local`. Official templates are a different asset category and must not be conflated with private organization submissions.
- **Semester reports:** `OrgReportDocument` stages private originals/generated reports with the associated `OrgReportStatus`; JSON `financial_summary` and `accomplishment_summary` preserve source-derived report packets. File saving and submission/review are separate actions.
- **Archive:** `ArchiveDocument.file_disk` is explicit metadata. Current uploads explicitly write `'local'`; current archive access requires that disk plus authorized folder/organization access. The exported SQL's old default of `'public'` does **not** override these current controller rules.
- **Community photos:** new images are stored on `local` and served by an authenticated, audience-authorized image route with private/no-store responses. The route can also read existing stored public-disk images through the same controlled access path; that compatibility is not a reason to publish direct storage URLs.
- **TOSA:** requirement paths must remain inside the owning SR-code directory on `local`; `TosaApplicant` checks supported requirement keys, path ownership and actual file validity before returning a file. Do not resolve a stored path directly to a public asset URL.
- **Native accomplishment evidence:** evidence arrays contain metadata for private evidence images; the service also generates the semester DOCX on `local`. Report views are authorized before file access.

Evidence: `app/Http/Controllers/OfficePortalController.php` (activity attachments, renewal submission, archive upload/view and report upload), `CommunityFeedController::store/image`, `AccomplishmentReportController::evidence`; `app/Models/TosaApplicant.php`, `OrgRenewalDocument.php`, `OrgReportDocument.php`; `app/Services/AccomplishmentReportService.php`.

## TOSA stores a packet and evaluator state

### Ownership, revision and resubmission

`TosaApplicant` explicitly uses `mysql` and casts `requirements` and `score_evaluation` to arrays, GWA to float and `gwa_verified` to boolean. The applicant is looked up using the authenticated `UserAccount`'s **trimmed SR code**, not an inferred `student_id` FK. The local export has neither an SR-code FK nor a unique applicant SR-code index; see [[Application Relationships and Schema Boundaries#TOSA uses an SR-code lookup, not a student-id relationship|lookup limitations]].

- Initial submission saves the **whole required packet**. Each required section must either be supplied or have a readable, valid saved private original.
- `studentSubmissionLocked()` locks an accepted status/subsection and any submitted packet whose subsection is not `returned`. A returned submitted packet is the revision path; it is not a free-edit state for every submitted application.
- Resubmission writes `status = submitted` and `subsection = screening`, restoring the lock.
- If files are replaced, the submission controller clears GWA, its verification flag, remarks and the stored score evaluation before saving. It retains supported saved requirement metadata where appropriate; it does not create a separate revision model/table.
- OSO review changes to GWA/verification, or a return/rejection, invalidate the evaluation: the service retains valid input as a recomputed **draft**, clears final verifier metadata and records an invalidation reason; malformed evaluation input is discarded. This is stored-state invalidation, not merely hiding a score in the UI.

Evidence: `TosaApplicant::studentSubmissionLocked/requirementPath/ownedRequirementFile/requirementFile`; `StudentPortalController::myTosaApplication` and whole-packet submission; `OfficePortalController` TOSA review transaction; `TosaScoringService::invalidate`.

### Evaluator scoring and ranking

`TosaScoringController` persists `score_evaluation` JSON on the applicant, not on a new score model. The packet includes the rubric version, draft/finalized state, validated evaluator input, calculated breakdown/entries, verifier metadata and timestamps. Finalization requires an active submitted packet in an allowed review subsection, verified eligibility, verified GWA from 1.00 to 3.00, a consecutive academic year and available original files for every required section.

`TosaScoringService`:

- Calculates server-side integer **milli-points** (1,000 units per point), using a 40-point academic / 30-point leadership / 30-point social maximum with category caps. Seminar scoring uses attended days, capped at five; local leadership is independent of social scoring.
- Recognizes only structurally valid packets for the current rubric and recomputes the stored breakdown/entries rather than trusting supplied totals.
- Ranks only submitted applicants in the accepted subsection with valid finalized evaluations, verified matching GWA and a valid academic-year cohort.
- Preserves equal-score ties with competition ranks; a tie crossing tenth place can include more than ten applicants. IDs stabilize display order and do not break merit ties.

The student-facing score/calculation section was removed, **not** evaluator persistence, scoring, ranking or workbook endpoints. `TosaWorkbookController`/`TosaWorkbookService` remain the workbook path. Do not describe the portal display change as deleting backend scoring.

**Local-schema caveat:** the model casts and current migration describe `score_evaluation`, but the attached local table lacks that column. This reference makes no claim that scoring can persist against the exported local structure without the appropriate schema addition; no migration is run by this documentation update.

Evidence: `app/Http/Controllers/TosaScoringController.php`; `app/Services/TosaScoringService.php`; migration `database/migrations/2026_10_11_000001_add_score_evaluation_to_tosa_applicants.php`; repository `CHANGELOG.md` student-TOSA display change.
