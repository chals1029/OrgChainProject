---
title: Ballot Sealing & Hash Chaining
tags:
  - blockchain
  - sealing
  - ballots
  - transactions
created: 2026-08-20
status: active
---

# 🧾 Ballot Sealing & Hash Chaining

> [!info] Sealing Pipeline
> Ballot sealing is the cryptographic process of transforming a voter's candidate selections into an immutable blockchain record with a tamper-evident digital receipt.

---

## 🔄 Detailed Sealing Lifecycle

```mermaid
sequenceDiagram
    autonumber
    actor Voter as Student Voter
    participant Router as Voting Router
    participant VoterCtrl as VoterController
    participant Chain as VoteBlockchain
    participant MySQL as MySQL DB
    participant Ledgers as Node Ledgers (1, 2, 3)

    Voter->>Router: POST /voting-system/vote/submit
    Router->>VoterCtrl: submitBallot(request)
    
    VoterCtrl->>MySQL: Check election status = 'open'
    VoterCtrl->>MySQL: Check voter has_voted = false
    VoterCtrl->>VoterCtrl: Validate position limits (radio: 1 choice, checkbox: <= max)

    VoterCtrl->>Chain: seal(electionId, refCode, voterId, choices, createdAt)
    
    Chain->>MySQL: Query latest block_hash for electionId
    alt Is First Ballot
        Chain-->>Chain: previous_hash = 0000...0000 (Genesis)
    else Subsequent Ballot
        Chain-->>Chain: previous_hash = Last Ballot block_hash
    end

    Chain->>Chain: Compute ballot_root & voter_commitment
    Chain->>Chain: Compute block_hash
    Chain->>Ledgers: Append block to Node-1, Node-2, Node-3
    Chain-->>VoterCtrl: Return Seal Result (Hashes & Confirmations)

    VoterCtrl->>MySQL: INSERT INTO votes (Anonymized Choices)
    VoterCtrl->>MySQL: INSERT INTO vote_receipts (Hashes & Nodes Confirmed)
    VoterCtrl->>MySQL: UPDATE voters SET has_voted = 1, voted_at = NOW()

    VoterCtrl-->>Voter: Display Digital Receipt (Reference Code + Block Hash)
```

---

## 🧾 Voter Digital Receipt Attributes

Upon successful ballot sealing, the voter receives a cryptographic receipt containing:
- **Reference Code**: Unique identifier (e.g., `VOTE-8B41D9EA02`)
- **Sealed Block Hash**: 64-character hexadecimal SHA-256 fingerprint
- **Ballot Root**: Merkle root of their validated choices
- **Nodes Confirmed**: Confirmation count across the 3 independent ledgers (`3/3 Nodes Verified`)
- **QR Code & Verification URL**: Link to verify receipt integrity at `/voting-system/admin/chain-verify`

```text
=====================================================
          ORGCHAIN OFFICIAL BALLOT RECEIPT
=====================================================
Reference Code:    VOTE-8B41D9EA02
Election ID:       1 (SSC General Elections 2026)
Timestamp:         2026-08-20 15:35:12
Previous Hash:     9c8e17094b219084c0128741b2094719...
Block Hash:        a1b2c3d4e5f60718293a4b5c6d7e8f90...
Ballot Root:       5e884898da28047151d0e56f8dc62927...
Nodes Confirmed:   3 / 3 Synchronized
=====================================================
```
