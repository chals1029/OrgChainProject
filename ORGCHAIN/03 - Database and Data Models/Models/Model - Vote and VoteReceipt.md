---
title: Model - Vote & VoteReceipt
tags: [models, blockchain, voting]
created: 2026-08-20
---

# 📜 Model: Vote & VoteReceipt

### 🗳️ `Vote` (Native PDO)
- **Table**: `votes`
- **Fields**: `id`, `election_id`, `voter_id`, `position_id`, `candidate_id`, `created_at`.
- **Constraint**: `UNIQUE(election_id, voter_id, position_id, candidate_id)`.

### 🧾 `VoteReceipt` (Native PDO)
- **Table**: `vote_receipts`
- **Fields**: `id`, `election_id`, `voter_id`, `reference_code` (UK), `previous_hash`, `block_hash`, `ballot_root`, `voter_commitment`, `nodes_confirmed`, `node_confirmations`, `created_at`.
