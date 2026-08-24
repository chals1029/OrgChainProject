---
title: Model - CommunityPost, Comment, Like
tags: [models, social, community]
created: 2026-08-20
---

# 💬 Model: CommunityPost, Comment, Like

### 📝 `CommunityPost` (Eloquent)
- **Table**: `community_posts`
- **Fields**: `id`, `student_id`, `activity_id`, `body`, `image_path`, `likes_count`, `comments_count`.

### 💬 `CommunityComment` (Eloquent)
- **Table**: `community_comments`
- **Fields**: `id`, `post_id`, `student_id`, `body`.

### ❤️ `CommunityLike` (Eloquent)
- **Table**: `community_likes`
- **Fields**: `id`, `post_id`, `student_id`.
- **Constraint**: `UNIQUE(post_id, student_id)`.
