---
title: Maintenance, Resync & Ledger Purge Runbook
tags: [operations, blockchain, maintenance, runbook]
created: 2026-08-20
---

# 🧹 Maintenance, Resync & Ledger Purge Runbook

> [!danger] Data Recovery & Integrity
> Procedures for recovering the 3-node blockchain ledgers from database state or purging test ballots.

---

## 🔄 Rebuilding Node Ledgers from Database
If any of the `storage/app/voting/chain/node-{1,2,3}/*.jsonl` files become corrupted or out of sync:

```powershell
php artisan tinker
```
```php
$chain = new App\VotingSystem\Core\VoteBlockchain();
$chain->resyncNodeLedgers(1); // 1 = Election ID
```
*Effect*: Reads all sealed receipts in chronological order and atomically rewrites all 3 node ledger files.

---

## 🗑️ Purging All Blockchain Ledgers (Dev Only)
```php
$chain = new App\VotingSystem\Core\VoteBlockchain();
$chain->purgeAllLedgers();
```
