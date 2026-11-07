# OrgChain Project Standards & Permanent Invariants

## 1. Mandatory Knowledge System: Graphify & Obsidian Vault
- **Always consult the Obsidian Vault (`ORGCHAIN/`) and Graphify FIRST:**
  - Before doing blind codebase scans, broad greps, or guessing file paths, always check the **Obsidian Vault (`ORGCHAIN/`)** and **Graphify Project Graph** (`ORGCHAIN/00 - Home and Index/Graphify Project Graph.md` and `%USERPROFILE%\.graphify\orgchain\graphify-out\graph.json`).
  - Master entry points:
    - `ORGCHAIN/00 - Home and Index/System Map MOC.md` (Master Map of Content)
    - `ORGCHAIN/00 - Home and Index/Dashboard.md` (System overview and current status)
    - `ORGCHAIN/00 - Home and Index/Quick Reference and Cheatsheet.md` (Quick lookups)
  - Subsystems & Specs:
    - `ORGCHAIN/02 - Subsystems and Modules/` (Detailed module blueprints)
    - `ORGCHAIN/03 - Database and Data Models/` (Schemas, Eloquent models, and ERDs)
    - `ORGCHAIN/04 - Workflows and Business Processes/` (TOSA, Renewal, Proposals, Budget)
    - `ORGCHAIN/05 - API and Route Specifications/` (Routes, middlewares, controller actions)
- **Graphify Maintenance:**
  - When significant structural changes occur, refresh the graph using:
    `powershell -ExecutionPolicy Bypass -File scripts/refresh-graphify.ps1`
- **Permanent Rule:** Never wait for the user to remind about Graphify and Obsidian. Use them proactively in every task and session for fast finding, accurate mapping, and immediate responses.

## 2. Orchestration & Implementation Architecture
- **Orchestrator / Planner:** `google-antigravity/gemini-3.8-flash:high` (Sole planner, architect, reviewer, and verifier).
- **Worker / Implementation:** `openai-codex/gpt-6.1-terra` via `task` subagent (`token-lean`, `token-balanced`, or `token-deep` depending on task complexity).

## 3. Deployment Guardrails
- **GitHub Push:** Do NOT push to GitHub unless the user explicitly asks to push.
- **VPS Deployment:** Deploy to live VPS (`/srv/orgchain/current`) when requested, ensuring cache clearing (`artisan view:clear`), permissions, and health checks (`curl ... /up`) pass.
## 4. Communication Tone
- **Language Style:** Natural Taglish (around 70% basic English, 30% Tagalog).
- **Tone:** Direct, concise, technical, evidence-first, and no unnecessary ceremony.
