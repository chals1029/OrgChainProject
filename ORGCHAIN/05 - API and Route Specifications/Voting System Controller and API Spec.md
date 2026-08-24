---
title: Voting System Controller & API Spec
tags: [api, voting, kernel, router]
created: 2026-08-20
---

# 🗳️ Voting System Controller & API Spec

> [!info] Dedicated Voting Sub-App
> The voting subsystem is dispatched via `App\VotingSystem\Kernel` and routed by `App\VotingSystem\Core\Router`.

---

## 🛣️ Internal Voting Endpoints

| Internal Route | Handler Controller | Purpose |
| :--- | :--- | :--- |
| `GET /` | `HomeController@index` | Public voting landing page |
| `GET /auth/google` | `HomeController@googleAuth` | Voter Google OAuth redirection |
| `GET /auth/google/callback` | `HomeController@googleCallback` | Google OAuth token exchange |
| `GET /vote/ballot` | `VoterController@ballot` | Active election ballot form |
| `POST /vote/submit` | `VoterController@submit` | Ballot choices validation & VoteChain seal |
| `GET /vote/receipt` | `VoterController@receipt` | Sealed digital ballot receipt |
| `GET /ssc-access-...` | `AdminAuthController@login` | Staff / Admin credential login |
| `GET /admin/dashboard` | `AdminController@dashboard` | Election administration hub |
| `GET /admin/candidates` | `AdminController@candidates` | Candidate & party registration |
| `GET /admin/chain-verify` | `AdminController@chainVerify` | Multi-node blockchain verification tool |
| `GET /ssc-canvassing-dashboard-...` | `AdminController@canvassingDashboard` | Real-time voter turnout & summary |
| `GET /ssc-canvassing-tally-...` | `AdminController@canvassingTally` | Live vote tally per candidate |
| `GET /ssc-canvassing-reports-...` | `AdminController@canvassingReports` | Certified printable reports |
