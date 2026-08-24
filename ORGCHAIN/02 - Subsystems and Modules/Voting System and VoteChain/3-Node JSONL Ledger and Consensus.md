---
title: 3-Node JSONL Ledger & Local Consensus
tags:
  - blockchain
  - consensus
  - nodes
  - redundancy
  - ledger
created: 2026-08-20
status: active
---

# 🔗 3-Node JSONL Ledger & Local Consensus

> [!abstract] Multi-Node Redundancy
> While traditional distributed blockchains rely on Proof-of-Work (PoW) or Proof-of-Stake (PoS) across decentralized internet nodes, **OrgChain** implements an optimized **3-Node Synchronous File Ledger Consensus** designed for institutional self-hosting.

---

## 🏛️ 3-Node Architecture Overview

```mermaid
graph TD
    SEAL[VoteBlockchain::seal] --> LOCK[Acquire Election Lock]
    LOCK --> GEN_BLOCK[Generate Sealed Block Payload]
    
    par Write Node 1
        GEN_BLOCK --> N1["📁 Node 1 Ledger<br/><code>storage/app/voting/chain/node-1/election-1.jsonl</code>"]
    and Write Node 2
        GEN_BLOCK --> N2["📁 Node 2 Ledger<br/><code>storage/app/voting/chain/node-2/election-1.jsonl</code>"]
    and Write Node 3
        GEN_BLOCK --> N3["📁 Node 3 Ledger<br/><code>storage/app/voting/chain/node-3/election-1.jsonl</code>"]
    end

    N1 --> VERIFY{Verify All 3 Node Writes}
    N2 --> VERIFY
    N3 --> VERIFY

    VERIFY -- 3/3 Nodes Confirmed --> COMMIT[Commit to MySQL + Return Receipt]
    VERIFY -- Partial Failure --> RETRY[Log Desync + Trigger Alarm]
```

---

## 📁 Ledger Directory Layout

On the local server, the ledgers are completely partitioned by node directory:

```text
storage/app/voting/chain/
├── node-1/
│   └── election-1.jsonl
├── node-2/
│   └── election-1.jsonl
└── node-3/
    └── election-1.jsonl
```

---

## 🔄 Synchronous Write & Append Integrity

When a ballot is cast:
1. The block is verified against the previous block hash.
2. The payload is appended to `node-1`, `node-2`, and `node-3` sequentially with `FILE_APPEND | LOCK_EX`.
3. The confirmation status is recorded in MySQL `vote_receipts.nodes_confirmed` (expecting `3`).
4. The exact status of each node write is serialized into `vote_receipts.node_confirmations` JSON:

```json
[
  {"node": 1, "status": "ok", "bytes": 482},
  {"node": 2, "status": "ok", "bytes": 482},
  {"node": 3, "status": "ok", "bytes": 482}
]
```

---

## 🛠️ Ledger Disaster Recovery & Resynchronization

If a node ledger file is corrupted, accidentally deleted, or tampered with:
- The system administrator can execute `VoteBlockchain::resyncNodeLedgers($electionId)`.
- The engine reads all sealed receipts from the MySQL database in strict chronological order and rebuilds all three `.jsonl` ledgers from scratch.
- See full execution commands in [[Maintenance Resync and Ledger Purge Runbook]].
