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
> This reference describes current source. The linked local ERD is a structure-only snapshot, not production schema. No model cast or migration file proves that a particular database has been migrated.

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
- OVCAA receives read-only access when enabled; legacy evaluator labels do not grant editing.
- SO, SDO and OC receive no TOSA clearance.
- Only OSO may verify documents or return a packet for correction.

Evidence: `config/auth.php`; `app/Models/UserAccount.php`, `Student.php`, `OfficeUser.php`; `app/Http/Controllers/TosaShortlistController.php`.

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
| TOSA | Eloquent `TosaApplicant`, `TosaHistoryEvent`, `TosaShortlist`; behavior in `TosaRequirements`, `TosaWorkflowService` | See packet, manual verification and publication below |
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

## TOSA stores a packet and manual review

`TosaApplicant` uses `mysql`; the authenticated student's trimmed SR code identifies the packet. Requirements and manual review are stored as JSON. Older score and committee metadata are retained but not used.

- A submitted packet contains all seven readable private originals and stays locked until OSO returns it for correction.
- Resubmission preserves unchanged files and clears previous document verification.
- OSO manually reviews each current document and confirms once when adding the applicant to the draft; verification does not certify its contents. The system does not input GWA, calculate scores or rank applicants.
- `TosaHistoryEvent` records actual new TOSA actions, and `TosaShortlist` stores a private draft separately from the explicitly published names/programs. Neither fabricates earlier activity.

Evidence: `TosaApplicant::studentSubmissionLocked/requirementPath/ownedRequirementFile/requirementFile`; `StudentPortalController::submitTosaApplication`; `TosaShortlistController`; `TosaWorkflowService`; `TosaHistoryEvent`; `TosaShortlist`.
