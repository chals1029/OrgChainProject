---
title: Master Map of Content (MOC)
tags:
  - moc
  - index
  - orgchain
created: 2026-08-20
status: active
---

# 🗺️ Master Map of Content (MOC)

This Map of Content indexes every architectural document, module specification, database schema, operational runbook, and workflow guide contained within the **OrgChain Obsidian Vault**.

---

## 🏛️ 01 - System Architecture & Foundation
- [[System Overview and Vision]]: Problem statement, institutional context at BatStateU, and strategic vision.
- [[High-Level Technical Architecture]]: Monolith architecture, subsystem modularity, and storage layers.
- [[End-to-End Data Flow]]: Sequence diagrams for proposal reviews, voting seals, and budget auditing.
- [[Security Architecture and Guardrails]]: SecurityGuard IDS, adaptive IP throttling, SQL injection detection, and rate windows.
- [[Tech Stack and Dependencies]]: Complete matrix of PHP 8.3, Laravel 13, Composer packages, and Node dependencies.

---

## 🗳️ 02 - Subsystems & Modules

### 🗳️ Voting System & VoteChain
- [[VoteChain Cryptographic Engine]]: Cryptographic specification, SHA-256 blocks, Merkle ballot roots.
- [[3-Node JSONL Ledger and Consensus]]: Multi-node redundancy, file locking, append-only integrity on storage.
- [[Ballot Sealing and Hash Chaining]]: Sealing lifecycle from voter submission to 3-node confirmation.
- [[Receipt Verification and Audit]]: Verification interface, cryptographic audit trail, tamper detection.
- [[Real-Time Canvassing and Tally]]: Real-time election canvassing, vote aggregation, export reports.

### 🏢 Office Portal & Administrative Desk
- [[Multi-Tier Office Roles SO OSO SDO OVCAA]]: Governance tier hierarchy, role permissions, and navigation badges.
- [[Activity Proposal Approval Pipeline]]: 4-stage document verification workflow and proposal lifecycle.
- [[Budget Utilization and OCR Receipts]]: Financial accounting, expense itemization, OCR receipt verification.
- [[Interactive Activity Calendar]]: Campus activity scheduling, month aggregation, and status color codes.
- [[Document Archive and Compliance Repository]]: Organization folder structure, multi-file uploads, semester archiving.

### 🎓 Student Portal & Community Hub
- [[Student Profile and Authentication]]: SR-Code identity system, profile management, and sessions.
- [[Community Feed Posts and Likes]]: Interactive social feed, event-linked posts, threaded comments, and likes.
- [[Transparency and Budget Visibility]]: Public accountability dashboard and fund utilization tracking.

### 🔐 Authentication & Access Control
- [[Google OAuth2 and BatStateU Domain Filter]]: Google OAuth2 integration with `@g.batstate-u.edu.ph` enforcement.
- [[6-Digit Email OTP Verification]]: Email-based one-time password fallback system.
- [[Obfuscated Admin and Office Route Endpoints]]: Obscured staff/admin routes for defense-in-depth protection.
- [[Session Management and Lifetime Policies]]: Multi-guard sessions, idle timeouts, and Laravel session bridge.

---

## 🗄️ 03 - Database & Data Models
- [[Database Schema and ERD]]: Full Entity-Relationship Diagram and table constraints.
- [[Eloquent and Core Models Reference]]: Overview index of all ORM and Native models.
  - [[Model - AdminUser and OfficeUser]]: Staff identity and administrative role models.
  - [[Model - Student and UserAccount]]: Student identity and profile models.
  - [[Model - Election Position Candidate]]: Ballot configuration and candidate data models.
  - [[Model - Vote and VoteReceipt]]: Anonymized ballots and cryptographic receipts.
  - [[Model - InCampusActivitySubmission and OrgActivity]]: Event submissions and activity tracking.
  - [[Model - BudgetItem and ExpenseReceiptReview]]: Budget accounting and OCR review models.
  - [[Model - CommunityPost Comment Like]]: Social community models.
  - [[Model - ArchiveFolder and ArchiveDocument]]: Document repository models.
  - [[Model - AuditLog and SecurityEvent]]: Forensics, audit trails, and IDS event logging.
- [[Database Seeders and Demo Fixtures]]: Seeder inventory, test accounts, and default seed data.

---

## 📋 04 - Workflows & Institutional Governance
- [[In-Campus Activity Proposal Checklist]]: 10-point BatStateU compliance checklist for on-campus events.
- [[Local Off-Campus Compliance and CHED Workflow]]: CHED CMO regulations, travel matrices, passenger lists, and waivers.
- [[Election Lifecycle and Tallying Flow]]: From election setup to canvassing certification.
- [[Expense Review and Financial Liquidation Flow]]: From receipt scan to budget deduction.

---

## 🌐 05 - API & Routing Specifications
- [[Web and Portal Route Map]]: Complete routing table for Web, Portal, and Office Desk.
- [[Voting System Controller and API Spec]]: Voting System Kernel and internal routing endpoints.
- [[Office Portal API and Action Endpoints]]: Office controller endpoints and document handlers.
- [[Middleware and Request Filters]]: Guard authentications and security middleware filters.

---

## 🚀 06 - Operations & Runbooks
- [[Local Development Setup Laragon PHP 8.3]]: Installation, Laragon vhost setup, artisan commands.
- [[Multi-Laptop 3-Node Blockchain Setup Runbook]]: Complete multi-laptop node topology, Cloudflare tunnels, and RPC setup.
- [[Environment Variables dot-env Specification]]: `.env` parameter reference and default values.
- [[Maintenance Resync and Ledger Purge Runbook]]: Blockchain recovery, ledger resynchronization, cache clearing.
- [[Security Incident and Rate Limit Response Runbook]]: Incident analysis, SQL injection mitigation, IP unbanning.
- [[Testing and Quality Assurance Guide]]: Running PHPUnit test suites and assertions.

---

## 🔮 07 - Roadmap & Strategic Planning
- [[Future Milestones and Features]]: P2P distributed nodes, cloud OCR, biometric integration.
- [[Known Limitations and Technical Debt]]: Monolith-hosted multi-node considerations and session bridging.
