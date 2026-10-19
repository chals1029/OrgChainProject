---
title: Master Map of Content (MOC)
tags: [moc, index, orgchain]
created: 2026-08-20
updated: 2026-10-16
status: active
---

# System Map

Curated navigation for current documentation. Start at [[Dashboard]] or [[Current System Guide]]. Dated session notes remain history; they do not override the current guides.

## Architecture

- [[System Overview and Vision]] — purpose, roles and actual limits.
- [[High-Level Technical Architecture]] — application, data and service boundaries.
- [[End-to-End Data Flow]] — approval, document and evidence lifecycles.
- [[Tech Stack and Dependencies]] — versions declared by the project manifests.
- [[Security Architecture and Guardrails]] — authentication, authorization, private files and security boundaries.
- [[Blockchain Architecture - Complete]] — detailed Besu/anchor reference; use [[VPS Pilot Deployment and Status]] for the recorded deployment and treat old setup examples as environment-specific.

## Student and office workflows

- [[Student Profile and Authentication]]
- [[Community Feed Posts and Likes]]
- [[Transparency and Budget Visibility]]
- [[Multi-Tier Office Roles SO OSO SDO OVCAA]] — includes OC final approval despite the historical filename.
- [[Activity Proposal Approval Pipeline]]
- [[Budget Utilization and OCR Receipts|Budget utilization and receipt records]] — manual receipt entry, not OCR.
- [[Organization Renewal Filing Window]]
- [[Semester AR and FR Submission Workflow]] — independent reports and locks.
- [[TOSA Application and Review Workflow]]
- [[Interactive Activity Calendar]]
- [[Document Archive and Compliance Repository]]
- [[In-Campus Activity Proposal Checklist]]
- [[Local Off-Campus Compliance and CHED Workflow]]
- [[Expense Review and Financial Liquidation Flow]]

## Database and ERD

- [[Database Schema and ERD]] — local physical schema, portable exports and code-defined relationships.
- [[Application Relationships and Schema Boundaries]] — verified logical links, separate identities and missing-FK explanations.
- [[Eloquent and Core Models Reference]]
- [[Database Seeders and Demo Fixtures]] — fixtures are not production identities.
- [[Model - AdminUser and OfficeUser]]
- [[Model - Student and UserAccount]]
- [[Model - Election Position Candidate]]
- [[Model - Vote and VoteReceipt]]
- [[Model - InCampusActivitySubmission and OrgActivity]]
- [[Model - BudgetItem and ExpenseReceiptReview]]
- [[Model - CommunityPost Comment Like]]
- [[Model - ArchiveFolder and ArchiveDocument]]
- [[Model - AuditLog and SecurityEvent]]
- [[Model - OrgRenewalWindow Submission Document]]

## Authentication and route reference

- [[Google OAuth2 and BatStateU Domain Filter]]
- [[6-Digit Email OTP Verification]]
- [[Obfuscated Admin and Office Route Endpoints]]
- [[Session Management and Lifetime Policies]]
- [[Web and Portal Route Map]]
- [[Office Portal API and Action Endpoints]]
- [[Voting System Controller and API Spec]]
- [[Middleware and Request Filters]]

## Voting and blockchain

- [[VoteChain Cryptographic Engine]]
- [[3-Node JSONL Ledger and Consensus]] — explicit file-driver/legacy reference, not the VPS Besu topology.
- [[Ballot Sealing and Hash Chaining]]
- [[Receipt Verification and Audit]]
- [[Real-Time Canvassing and Tally]]
- [[Election Lifecycle and Tallying Flow]]

## Operations

- [[VPS Pilot Deployment and Status]] — recorded deployment, readiness signals and safe boundaries.
- [[Quick Reference and Cheatsheet]]
- [[Local Development Setup Laragon PHP 8.3]]
- [[Environment Variables dot-env Specification]]
- [[Testing and Quality Assurance Guide]]
- [[Playwright Console Smoke Testing]]
- [[Security Incident and Rate Limit Response Runbook]]
- [[Estimated Deployment and Infrastructure Costing]] — estimates and pricing assumptions, not a current invoice.
- [[Multi-Laptop 3-Node Blockchain Setup Runbook]] — alternative demonstration topology, not the current recorded VPS setup.
- [[Maintenance Resync and Ledger Purge Runbook]] — potentially destructive maintenance; do not run as a normal deployment step.

## History and planning

- [[Session Log 2026-10-04 Organization Renewal Qualification and Document Workflow]]
- [[Session Log 2026-09-21 Activity Budget Lifecycle]]
- [[Session Log 2026-09-20 Activity Filing and Document Editor]]
- [[Future Milestones and Features]]
- [[Known Limitations and Technical Debt]]

Find other dated session notes in the operations folder. Proposed roadmap features are not implemented capabilities.
