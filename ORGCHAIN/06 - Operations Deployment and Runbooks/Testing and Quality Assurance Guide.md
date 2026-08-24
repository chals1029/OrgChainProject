---
title: Testing & Quality Assurance Guide
tags: [testing, phpunit, qa, quality]
created: 2026-08-20
---

# 🧪 Testing & Quality Assurance Guide

> [!info] Test Automation
> OrgChain utilizes **PHPUnit 12.x** for automated backend testing.

---

## 🏃 Running Test Suites

```powershell
# Run all tests
php artisan test

# Run a specific test class
php artisan test --filter=VoteBlockchainTest
```

### Key Areas Covered by Tests:
1. **VoteBlockchain Sealing & Hash Chaining**: Verifies mathematical correctness of SHA-256 chain links.
2. **SecurityGuard Rate Limiting**: Tests rate limit triggers and 429 response enforcement.
3. **Multi-Tier Office Authentication**: Tests guard segregation and unauthorized redirect rules.
