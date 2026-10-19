---
title: Model - CommunityPost, Comment, Like
tags: [models, social, community]
created: 2026-08-20
updated: 2026-10-16
---

# Model: CommunityPost, Comment, Like

Related: [[Eloquent and Core Models Reference]] · [[Database Schema and ERD]] · [[Application Relationships and Schema Boundaries#Community is not the legacy students graph|qualified community relationship guide]] · [[Model - Student and UserAccount]]

## Current models all explicitly use `orgchain`

| Eloquent class in `app/Models/` | Table | Confirmed relationships |
| --- | --- | --- |
| `CommunityPost` | `orgchain.community_posts` | `student()` → `UserAccount` through `student_id` / `user_id`; `activity()` → `OrgActivity` through `activity_id`; `comments()` and `likes()` through `post_id` |
| `CommunityComment` | `orgchain.community_comments` | `post()` through `post_id`; `student()` → `UserAccount.user_id`; `likes()` → `CommunityCommentLike.comment_id` |
| `CommunityLike` | `orgchain.community_likes` | `post()` through `post_id`; `student()` → `UserAccount.user_id` |
| `CommunityCommentLike` | `orgchain.community_comment_likes` | `comment()` through `comment_id`; `student()` → `UserAccount.user_id` |

These links are **application-defined**, not declared FKs in the local `orgchain` community export. In particular, `student_id` stores the current `UserAccount.user_id`, not the legacy `students.id`. `OrgActivity` explicitly uses `mysql` (represented as `votingsystem.org_activities` in the local snapshot), so the optional post/activity link crosses connections.

## Stored post and interaction data

- Posts store `student_id`, optional `activity_id`, `body`, `image_path`, count fields, `feeling`, `tagged_users` and `audience`. `tagged_users` is not established as a relational join by its name.
- Comments store `post_id`, `student_id` and `body`.
- Post reactions live in **`community_likes.reaction`**. The controller supports `like`, `love`, `care`, `haha`, `wow`, `sad`, `angry`. Choosing the same reaction removes it; choosing a different one updates it. The local unique index is `(post_id, student_id)`.
- Comment likes are a separate **toggle** in `community_comment_likes`, with a local unique index on `(comment_id, student_id)`; they are not seven-code post reactions.
- Current feed queries use actual relationship counts and apply audience-scoped search before pagination. Stored count columns alone do not establish the current totals or relationships.
- New community photos are stored on private `local` storage. The image route checks authenticated audience access and returns private/no-store responses; it can also read older stored public-disk files through that route. File metadata is not permission to expose a direct storage URL.

## Keep the legacy physical graph separate

The local **`votingsystem.community_posts`, `community_comments` and `community_likes`** tables also exist. Their physical FKs point to **`votingsystem.students.id`**, posts and activities as recorded in the unchanged [[ERD Exports/orgchain-local-erd.dbml|physical DBML]]. They are **not** the tables selected by the current community models. Do not merge their FK lines into the active `orgchain` model graph or silently rewrite the physical export.

The [[Database Schema and ERD|schema guide]] describes local structure only, not live production schema or record-level integrity.

## Source evidence

`app/Models/CommunityPost.php`, `CommunityComment.php`, `CommunityLike.php`, `CommunityCommentLike.php`, `UserAccount.php`, `OrgActivity.php`; `app/Http/Controllers/CommunityFeedController.php` (post/comment/reaction writes and image access), `StudentPortalController.php` (feed query). No posts, comments, account records or private images are included in this note.
