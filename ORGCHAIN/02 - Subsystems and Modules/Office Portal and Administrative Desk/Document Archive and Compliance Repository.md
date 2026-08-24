---
title: Document Archive & Compliance Repository
tags:
  - office
  - archive
  - compliance
  - documents
  - storage
created: 2026-08-20
status: active
---

# 📂 Document Archive & Compliance Repository

> [!abstract] Digital Document Vault
> The OrgChain Archive module (`/office-desk/archive`) provides a multi-semester document management system organized by student organization, semester, and color-coded folders.

---

## 🗂️ Folder & Document Architecture

```mermaid
graph TD
    ROOT[📁 Archive Vault] --> F1["📁 Red Folder: SSC 2nd Semester 2026"]
    ROOT --> F2["📁 Blue Folder: CICS Society 1st Semester 2026"]
    ROOT --> F3["📁 Green Folder: Red Cross Youth Compliance"]

    F1 --> D1["📄 Financial_Report_Q1.pdf"]
    F1 --> D2["📄 Activity_Matrix.docx"]
    F2 --> D3["📄 General_Assembly_Minutes.pdf"]
```

---

## 💾 Storage & File Integrity

All uploaded documents are stored securely on the local filesystem:
- **Physical Path**: `storage/app/public/archive_documents/`
- **Database Table**: `archive_documents` (`file_path`, `mime_type`, `file_size`, `uploaded_by`)
- **Folder Table**: `archive_folders` (`name`, `organization_name`, `semester`, `color`)
