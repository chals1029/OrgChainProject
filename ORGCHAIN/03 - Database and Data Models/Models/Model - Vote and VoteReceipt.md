---
title: Model - Vote & VoteReceipt
tags: [models, blockchain, voting]
created: 2026-08-20
updated: 2026-10-16
---

# Model: Vote & VoteReceipt

Related: [[Eloquent and Core Models Reference]] · [[Database Schema and ERD]] · [[Model - Election Position Candidate]]

## `Vote`: native PDO model

- **Class:** `App\VotingSystem\Models\Vote`, extending the voting subsystem's native `Core\Model`, not Laravel Eloquent.
- **Source:** `app/VotingSystem/Models/Vote.php`.
- **Choice table:** `votes`, represented as `votingsystem.votes` in the local export. Columns include `id`, `election_id`, `voter_id`, `position_id`, `candidate_id`, `created_at`.
- **Physical local constraints:** FKs to elections, voters, positions and candidates; unique `(election_id, voter_id, position_id, candidate_id)` prevents duplicate choice rows. That unique index alone is not the whole one-ballot rule.

`Vote::submit()` validates choices, starts a PDO transaction, loads the voter with `FOR UPDATE` on MySQL, rejects a previously voted voter, inserts a receipt and the selected choice rows, and conditionally marks the voter as voted. It commits these database writes together. Empty/absent choices represent implicit abstention for that position rather than invented candidate rows.

## `vote_receipts`: a table, not a standalone model class

The historical **note name is retained**, but there is **no standalone `VoteReceipt` class** in the current `app/VotingSystem/Models/` or `app/Models/` directories—neither native PDO nor Eloquent. `Vote::submit()` inserts the receipt directly using PDO.

The local `votingsystem.vote_receipts` structure contains:

- Identity: `id`, `election_id`, `voter_id`, `reference_code`, `created_at`.
- Proof/confirmation metadata: `previous_hash`, `block_hash`, `ballot_root`, `voter_commitment`, `nodes_confirmed`, `node_confirmations`.
- Chain-location metadata: `chain_driver`, `chain_tx_hash`, `chain_block_number`, `chain_contract_address`.
- **Physical FKs:** `election_id → elections.id`, `voter_id → voters.id`.
- **Unique indexes:** `reference_code` and `(election_id, voter_id)`.

Those FK/index claims refer to the unchanged [[ERD Exports/orgchain-local-schema.sql|local schema-only SQL]], not a fresh inspection of production.

## Database submission and chain sealing are distinct

After the database transaction commits, `Vote::submit()` calls `sealReceipt()`. That method invokes `VoteBlockchain::seal()` and updates the stored receipt's proof/chain metadata. The source catches/logs sealing failures rather than rolling back the already committed ballot. Consequently, **a receipt row or successful relational insert alone is not proof of a successful blockchain seal, quorum confirmation or current-contract verification**.

The schema attachments contain no ballot records, reference-code values or chain state. Keep relational FK evidence separate from runtime/on-chain proof verification; this note makes no claim that local or live ballots were verified.

## Source evidence

`app/VotingSystem/Models/Vote.php` (`submit`, `sealReceipt`); current model-directory inventory; exported `votes`/`vote_receipts` definitions and FK clauses. See [[Database Schema and ERD]] for local/live boundaries.
