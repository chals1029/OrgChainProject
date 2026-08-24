---
title: Eloquent & Core Models Reference
tags:
  - models
  - eloquent
  - pdo
  - database
created: 2026-08-20
status: active
---

# 📋 Eloquent & Core Models Reference

> [!info] Model Architecture
> OrgChain utilizes **Eloquent ORM** for general portal, activity, budget, and community management, alongside high-performance **Native PDO Models** for the Voting System sub-application.

---

## 🗂️ Model Directory & Index

| Category | Model Name | Implementation Type | Documentation Link |
| :--- | :--- | :--- | :--- |
| **Authentication & Staff** | `AdminUser`, `OfficeUser` | PDO / Eloquent | [[Model - AdminUser and OfficeUser]] |
| **Student Identity** | `Student`, `UserAccount` | Eloquent | [[Model - Student and UserAccount]] |
| **Ballot Structure** | `Election`, `Position`, `Candidate` | Native PDO | [[Model - Election Position Candidate]] |
| **Blockchain Ballots** | `Vote`, `VoteReceipt`, `Voter` | Native PDO | [[Model - Vote and VoteReceipt]] |
| **Activity Governance** | `InCampusActivitySubmission`, `OrgActivity` | Eloquent | [[Model - InCampusActivitySubmission and OrgActivity]] |
| **Finance & OCR** | `BudgetItem`, `ExpenseReceiptReview` | Eloquent | [[Model - BudgetItem and ExpenseReceiptReview]] |
| **Community Feed** | `CommunityPost`, `CommunityComment`, `CommunityLike` | Eloquent | [[Model - CommunityPost Comment Like]] |
| **Document Vault** | `ArchiveFolder`, `ArchiveDocument` | Eloquent | [[Model - ArchiveFolder and ArchiveDocument]] |
| **Security & Forensics** | `AuditLog`, `SecurityEvent` | Native PDO | [[Model - AuditLog and SecurityEvent]] |
