---
title: Model - Election, Position, Candidate
tags: [models, voting, election]
created: 2026-08-20
---

# 🗳️ Model: Election, Position, Candidate

### 🗳️ `Election` (Native PDO)
- **Table**: `elections`
- **Fields**: `id`, `title`, `status` (`pending`, `open`, `closed`), `start_at`, `end_at`, `instructions`, `announcement`.

### 📌 `Position` (Native PDO)
- **Table**: `positions`
- **Fields**: `id`, `election_id`, `title`, `selection_type` (`radio`, `checkbox`), `max_choices`, `sort_order`.

### 👤 `Candidate` (Native PDO)
- **Table**: `candidates`
- **Fields**: `id`, `position_id`, `name`, `party`, `image_path`, `image_blob`, `image_mime`, `sort_order`.
