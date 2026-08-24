---
title: Model - Student & UserAccount
tags: [models, students, auth]
created: 2026-08-20
---

# 🎓 Model: Student & UserAccount

### 🧑‍🎓 `Student` (Eloquent)
- **Table**: `students`
- **Fields**: `id`, `sr_code`, `name`, `email`, `password`, `college`, `program`, `year_level`, `avatar_path`, `is_active`.
- **Relations**: `hasMany(CommunityPost)`, `hasMany(CommunityComment)`, `hasMany(CommunityLike)`.

### 🪪 `UserAccount` (Eloquent)
- **Table**: `user_accounts`
- **Fields**: `id`, `user_id`, `org_id`, `sr_code`, `full_name`, `email`, `college`, `program`, `year_level`, `role`, `account_status`.
- **Purpose**: University master student registry.
