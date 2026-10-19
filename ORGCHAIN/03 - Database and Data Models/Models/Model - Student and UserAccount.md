---
title: Model - Student & UserAccount
tags: [models, students, auth]
created: 2026-08-20
updated: 2026-10-16
---

# Model: Student & UserAccount

Related: [[Eloquent and Core Models Reference]] · [[Database Schema and ERD]] · [[Application Relationships and Schema Boundaries]]

## `UserAccount`: current student identity

- **Class:** `App\Models\UserAccount`, an Eloquent authenticatable model.
- **Connection/table:** explicitly `orgchain`, table `user_accounts`.
- **Primary key:** **`user_id`**, not `id`. The model's `id` accessor returns `user_id`; it does not represent another physical column. The model enables incrementing identifiers and disables Eloquent timestamps; those model settings are not a replacement for the exported DDL.
- **Authentication:** `config/auth.php` maps the `student` guard's provider to `UserAccount::class`. `getAuthPassword()` reads the hidden `password_hash` attribute. `isActive()` checks whether `account_status` is `active`.
- **Identity fields:** `sr_code`, `full_name`, `email`, `college`, `program`, `year_level`, `role`, `account_status`, `org_id`. The `name` accessor falls back from full name to SR code/email; these are model attributes, not evidence of record contents.
- **Community relations:** `posts()`, `comments()` and `likes()` use child `student_id` with local owner key **`user_id`**. The corresponding child models explicitly use `orgchain` and resolve their `student()` relationship to this model.
- **Other current links:** feedback and RSVP write the authenticated student's identifier; TOSA instead looks up the applicant by the student's trimmed SR code. These are code-defined links, not automatically declared FKs.

The local `orgchain.user_accounts` export defines `user_id` as its primary key. Its `org_id` FK targets the separate older `orgchain.student_organizations.org_id` table; do not substitute the office registry's `votingsystem.student_organizations.id`.

## `Student`: legacy model, distinct table

- **Class:** `App\Models\Student`, an Eloquent authenticatable model using the inherited/default connection and conventional `students` table/`id` key.
- **Fields:** `sr_code`, `name`, `email`, `password`, `college`, `program`, `year_level`, `avatar_path`, `is_active`; password is hashed through its cast and the active flag is boolean.
- **Provider boundary:** this is **not** the currently configured student provider. Its `is_active`/`password` fields must not be substituted for `UserAccount.account_status`/`password_hash`.
- **Legacy relationship methods:** `posts()`, `comments()` and `likes()` remain in source, but their existence does not change the current community models' explicit `orgchain` connection or their `UserAccount.user_id` author target.

The local **`votingsystem.community_*`** tables retain physical FKs to **`votingsystem.students.id`**. Those are real exported legacy constraints, not the current **`orgchain.community_*`** model graph. See [[Application Relationships and Schema Boundaries#Community is not the legacy students graph|the qualified physical/logical distinction]]. Neither this note nor the local export claims that two records with the same numeric identifier are the same person.

## Source evidence

`config/auth.php`; `app/Models/UserAccount.php`, `Student.php`, `CommunityPost.php`, `CommunityComment.php`, `CommunityLike.php`, `CommunityCommentLike.php`; `StudentPortalController` feedback/RSVP/TOSA writes. Physical evidence: [[ERD Exports/orgchain-local-schema.sql|local schema-only SQL]]. No account records or credentials are included.
