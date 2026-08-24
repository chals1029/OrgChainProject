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

# 💬 Community Feed, Posts & Likes

> [!info] Campus Dialogue
> The Student Community Feed (`/portal/community`) provides a forum where students can discuss campus events, share project highlights, and comment on organizational achievements.

---

## 📱 Social Interaction Components

```mermaid
graph TD
    POST["📝 CommunityPost<br/>(body, image_path, likes_count, comments_count)"]
    POST -->|Has Many| COMMENT["💬 CommunityComment<br/>(post_id, student_id, body)"]
    POST -->|Has Many| LIKE["❤️ CommunityLike<br/>(post_id, student_id)"]
    POST -->|Belongs To| ACT["📅 OrgActivity (Optional Link)"]
```

---

## ⚡ Real-Time Post & Like Features

- **Activity Tagging**: Posts can be tagged with official university activities (`org_activities`).
- **Optimistic Like Toggle**: Toggle like status instantly with responsive counters.
- **Threaded Commenting**: Direct student-to-student discussions linked to verified student profiles.
