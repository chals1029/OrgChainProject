---
title: OrgChain System Dashboard
tags:
  - dashboard
  - moc
  - overview
  - batstateu
created: 2026-08-20
status: active
---

# 🏛️ OrgChain System Vault & Knowledge Base

> [!abstract] Executive Summary
> **OrgChain** is an enterprise-grade University Student Organization Governance, Transparency, and Cryptographic Voting Platform engineered specifically for **Batangas State University (BatStateU)**. It unifies administrative approval workflows, budget tracking with OCR receipt auditing, real-time student community engagement, and a localized **3-node cryptographic blockchain ledger** for tamper-evident campus elections.

---

## 🧭 Master Navigation & Maps of Content (MOC)

```mermaid
mindmap
  root((OrgChain Core))
    System Architecture
      [[System Overview and Vision]]
      [[High-Level Technical Architecture]]
      [[End-to-End Data Flow]]
      [[Security Architecture and Guardrails]]
      [[Tech Stack and Dependencies]]
    Voting and VoteChain
      [[VoteChain Cryptographic Engine]]
      [[3-Node JSONL Ledger and Consensus]]
      [[Ballot Sealing and Hash Chaining]]
      [[Receipt Verification and Audit]]
      [[Real-Time Canvassing and Tally]]
    Office Desk and Governance
      [[Multi-Tier Office Roles SO OSO SDO OVCAA]]
      [[Activity Proposal Approval Pipeline]]
      [[Budget Utilization and OCR Receipts]]
      [[Interactive Activity Calendar]]
      [[Document Archive and Compliance Repository]]
    Student Portal and Engagement
      [[Student Profile and Authentication]]
      [[Community Feed Posts and Likes]]
      [[Transparency and Budget Visibility]]
    Auth and Security
      [[Google OAuth2 and BatStateU Domain Filter]]
      [[6-Digit Email OTP Verification]]
      [[Obfuscated Admin and Office Route Endpoints]]
      [[Session Management and Lifetime Policies]]
    Database and Schemas
      [[Database Schema and ERD]]
      [[Eloquent and Core Models Reference]]
      [[Database Seeders and Demo Fixtures]]
    Compliance and Workflows
      [[In-Campus Activity Proposal Checklist]]
      [[Local Off-Campus Compliance and CHED Workflow]]
      [[Election Lifecycle and Tallying Flow]]
      [[Expense Review and Financial Liquidation Flow]]
    Operations and Runbooks
      [[Local Development Setup Laragon PHP 8.3]]
      [[Environment Variables dot-env Specification]]
      [[Maintenance Resync and Ledger Purge Runbook]]
      [[Security Incident and Rate Limit Response Runbook]]
      [[Testing and Quality Assurance Guide]]
```

---

## ⚡ Subsystem Matrix

| Subsystem | Primary Path | Target Persona | Key Technologies | Core Documentation |
| :--- | :--- | :--- | :--- | :--- |
| **🎓 Student Portal** | `/portal` | Students & Org Members | Laravel Blade, Tailwind CSS, Alpine.js | [[Student Profile and Authentication]] |
| **🏢 Office Desk** | `/office-desk` | SO, OSO, SDO, OVCAA Officers | Multi-guard Auth, File Pipeline, OCR | [[Multi-Tier Office Roles SO OSO SDO OVCAA]] |
| **🗳️ Voter Ballot** | `/voting-system` | University Voters | SHA-256 Chaining, Merkle Root | [[VoteChain Cryptographic Engine]] |
| **📊 Canvassing Desk** | `/voting-system/ssc-...` | Election Commissioners (SSC) | Live Canvassing Tally, PIN Auth | [[Real-Time Canvassing and Tally]] |
| **🛡️ Security Shield** | Global Middleware | System Administrators | SecurityGuard IDS, RateLimiter | [[Security Architecture and Guardrails]] |

---

## 📊 System Quick Facts

> [!info] Technical Snapshot
> - **Framework Backend**: Laravel 13.x running on **PHP 8.3**
> - **Blockchain Storage**: 3 localized synchronous JSONL ledgers in `storage/app/voting/chain/node-{1,2,3}/`
> - **Hashing Algorithm**: Standard SHA-256 (`previous_hash`, `block_hash`, `ballot_root`, `voter_commitment`)
> - **Institutional Scope**: Batangas State University - The National Engineering University
> - **Database Support**: MySQL (production/Laragon) with full SQLite fallback capability

---

## 🔗 Quick Actions & Jump Links

- 🧭 **Explore Master Index**: [[System Map MOC]]
- 🔑 **Credentials & Commands**: [[Quick Reference and Cheatsheet]]
- 🚀 **Run the System Locally**: [[Local Development Setup Laragon PHP 8.3]]
- 🗳️ **Inspect the Blockchain**: [[VoteChain Cryptographic Engine]]
- 🗄️ **Review Database Structure**: [[Database Schema and ERD]]
