---
title: Community Feed, Posts & Likes
tags:
  - community
  - feed
  - social
  - comments
  - likes
created: 2026-08-20
status: active
---

# Community Feed, Posts and Likes

The authenticated student feed is `/portal/community`. Posts contain student-written text, an optional photo and an optional linked activity. Empty feeds stay empty: no sample posts/comments, decorative reaction stacks or fabricated counters are inserted.

## Audience and private photos

- The selected `public`, `department` or `org` audience is persisted. Public means visible to authenticated students, not an unauthenticated public image URL.
- Department posts are scoped to the author's college; organization posts are scoped to organization membership. Publishing to `org` requires membership. Authors retain access to their own posts.
- New photos use the private `local` disk. `GET /portal/community/posts/{post}/image` rechecks the active student session and audience, then returns `Cache-Control: private, no-store`.
- Existing stored photos can be read through that same authorized route without relocating them. Direct `/storage/community/` URLs are denied; do not embed raw storage paths.
- Photos accept JPG/JPEG, PNG, WebP or GIF up to 4 MB. Choosing or cancelling a photo is local and does not publish.

## Real interactions and discovery

- Reaction and comment totals come from persisted relations, including zero. Most recent uses creation time; `sort=popular` orders by real reaction count and `sort=top` by real comment count. Creation time and then descending ID break ties deterministically.
- Seven persisted reactions are available: **Like, Love, Care, Haha, Wow, Sad and Angry**. Choosing the same reaction removes it; choosing another replaces the student's current reaction.
- Comments belong to their post and student; comment likes are also persisted. There is no separate nested/threaded-reply workflow documented here.
- `community_q` searches visible post text, author names and linked activity titles **before** ten-post pagination. Search retains the selected sort and cannot reveal another audience's posts.
- **Feed filters** is a native disclosure with audience (`community_audience=all|public|department|org`) and rolling date (`community_period=all|day|week|month`) controls. Date windows include their 1/7/30-day lower boundary and current time, excluding future timestamps. Filters narrow, never expand, the current visibility policy; they apply before pagination and survive search/sort/page changes.
- **New** means `now − 24 hours < created_at ≤ now`, independently of `updated_at`. The badge expires without removing the post, including when returning to a background tab.
- Comment entry is plain text, without the former emoji/photo/GIF/sticker toolbar. Tag people and its sample chooser are removed from the composer and new-post payload; existing stored metadata is not deleted.
- Success flashes dismiss after **four seconds**; validation errors remain visible.
- Publishing, commenting and deletion use the shared business-action confirmation. Cancelling preserves the draft/selected file and sends no mutation; browsing, searching, viewing likers and choosing files do not prompt.

## Routes

| Method | Route | Action |
| --- | --- | --- |
| GET | `/portal/community` | Visibility-scoped search, audience/date filters and sorting |
| POST | `/portal/community/posts` | Publish to the selected audience |
| GET | `/portal/community/posts/{post}/image` | Audience-authorized photo |
| GET | `/portal/community/posts/{post}/likers` | Actual reaction list/counts |
| POST | `/portal/community/posts/{post}/like` | Toggle/change a reaction |
| POST | `/portal/community/posts/{post}/comments` | Add a comment |
| POST | `/portal/community/comments/{comment}/like` | Toggle a comment like |
| DELETE | `/portal/community/posts/{post}` | Owner-only deletion |

Related: [[Transparency and Budget Visibility]] · [[Student Profile and Authentication]] · [[Web and Portal Route Map]]

## Source evidence

`routes/web.php`; `app/Http/Controllers/CommunityFeedController.php`; `StudentPortalController::portal()`; `resources/views/portal/index.blade.php` and `layout.blade.php`.
