---
title: Google OAuth2 & BatStateU Domain Filter
tags:
  - auth
  - oauth
  - google
  - security
created: 2026-08-20
status: active
---

# 🔑 Google OAuth2 & BatStateU Domain Filter

> [!info] Domain Restriction
> To ensure that only legitimate university members participate in voting and student discussions, OrgChain enforces institutional domain filtering on all Google OAuth2 flows.

---

## 🛡️ Domain Enforcement Mechanism

```mermaid
sequenceDiagram
    autonumber
    actor User as Student / Voter
    participant App as OrgChain OAuth Client
    participant Google as Google Identity Server

    User->>App: Click 'Sign in with BatStateU Google'
    App->>Google: Redirect with client_id, redirect_uri, scope=email profile
    User->>Google: Authenticate credentials
    Google-->>App: Return Authorization Code
    App->>Google: Exchange code for ID Token & User Info
    Google-->>App: Return User Profile (email, hd domain)
    
    App->>App: Check domain == 'g.batstate-u.edu.ph'
    alt Allowed Domain
        App->>App: Lookup / Provision Student & Login
        App-->>User: Redirect to Portal / Ballot
    else Foreign Domain (e.g. gmail.com)
        App-->>User: ⛔ HTTP 403 Access Denied: University Email Required
    end
```
