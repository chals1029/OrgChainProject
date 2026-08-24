---
title: Model - ArchiveFolder & ArchiveDocument
tags: [models, archive, documents]
created: 2026-08-20
---

# 📂 Model: ArchiveFolder & ArchiveDocument

### 📁 `ArchiveFolder` (Eloquent)
- **Table**: `archive_folders`
- **Fields**: `id`, `name`, `organization_name`, `semester`, `color`.

### 📄 `ArchiveDocument` (Eloquent)
- **Table**: `archive_documents`
- **Fields**: `id`, `archive_folder_id`, `name`, `original_name`, `file_path`, `mime_type`, `file_size`, `uploaded_by`.
