# Project Architecture & Orchestration Invariants

## 1. Orchestrator / Planner
- **Model:** `google-antigravity/gemini-3.8-flash:high`
- **Role:** Sole coordinator, architect, planner, reviewer, and verifier.
- **Responsibilities:**
  - Receive, clarify, and plan all user requests.
  - Research files, inspect diffs, and establish implementation contracts.
  - Direct and review delegated subagents.
  - Execute final smoke testing, test suites, and VPS deployments.
  - Do NOT write or edit source code directly for feature implementations or substantive bug fixes.

## 2. Implementation Subagent / Worker
- **Model:** `openai-codex/gpt-6.1-terra` (via `task` subagent tool)
- **Role:** Primary code implementation engine.
- **Workflow:**
  - Every non-trivial code implementation, refactor, or multi-line edit MUST be delegated to a `task` subagent powered by `gpt-6.1-terra`.
  - Use appropriate task agent profiles:
    - `token-lean` (`gpt-6.1-terra:low`): For bounded, straightforward code edits.
    - `token-balanced` (`gpt-6.1-terra:medium`): For standard feature implementation and debugging.
    - `token-deep` (`gpt-6.1-terra:high`): For complex multi-module logic or difficult bugs.
  - The worker modifies target files and returns results to the orchestrator for review and verification.

## 3. Deployment Guardrails
- **GitHub Push:** Do NOT push commits to GitHub unless explicitly instructed by the user.
- **VPS Deployment:** Deploy to live VPS (`/srv/orgchain/current`) when requested, always verifying with view cache clearing and health checks.

## 4. Mandatory Knowledge System: Graphify & Obsidian Vault
- **Primary Discovery Engine:** Always consult the **Obsidian Vault (`ORGCHAIN/`)** and the **Graphify Project Graph** (`ORGCHAIN/00 - Home and Index/Graphify Project Graph.md` and `%USERPROFILE%\.graphify\orgchain\graphify-out\graph.json`) FIRST before doing broad, blind codebase exploration or file searches.
- **Workflow & Knowledge Navigation:**
  - **Master Navigation:** Start at `ORGCHAIN/00 - Home and Index/System Map MOC.md`, `Dashboard.md`, and `Quick Reference and Cheatsheet.md` to instantly locate modules, controllers, views, schemas, and workflows.
  - **Workflows & Specs:** Check `ORGCHAIN/04 - Workflows and Business Processes/` and `ORGCHAIN/05 - API and Route Specifications/` for business rules, authorization tiers, and endpoint maps.
  - **Data Models & Database:** Consult `ORGCHAIN/03 - Database and Data Models/` for ERDs, Eloquent models, and column schemas.
  - **Subsystems:** Consult `ORGCHAIN/02 - Subsystems and Modules/` for deep subsystem specifications (Voting System, Office Portal, Student Portal, TOSA, Budget Utilization, etc.).
- **Graphify CLI & Refresh Script:**
  - Graphify binary: `%USERPROFILE%\.local\bin\graphify.exe`
  - Project graph cache: `%USERPROFILE%\.graphify\orgchain\graphify-out\graph.json`
  - To refresh the graph when significant structural changes occur, run:
    `powershell -ExecutionPolicy Bypass -File scripts/refresh-graphify.ps1`
- **Permanent Invariant:** This is a permanent, non-negotiable project standard. Do not wait for user reminders in future turns or sessions; always leverage Graphify and the Obsidian vault as the primary speed-and-accuracy accelerator for finding files, understanding architecture, and delivering answers.

## 5. Communication Tone
- **Language Style:** Use natural Taglish (around 70% basic English, 30% Tagalog).
- **Tone:** Direct, concise, technical, evidence-first, and no unnecessary ceremony.
