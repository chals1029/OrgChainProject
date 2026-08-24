---
title: 6-Digit Email OTP Verification
tags:
  - auth
  - otp
  - email
  - security
created: 2026-08-20
status: active
---

# 📨 6-Digit Email OTP Verification

> [!info] Passwordless OTP
> For students without active Google OAuth sessions or using browser kiosks, OrgChain provides a secure 6-digit email One-Time Password (OTP) verification service.

---

## 🔢 OTP Protocol & Rules

```mermaid
flowchart TD
    REQ[Enter SR-Code or Email] --> RATE{Rate Limit Check<br/>Max 3 codes / 15 min}
    RATE -- Exceeded --> DENY[⛔ Request Blocked: Wait 15 minutes]
    RATE -- OK --> GEN[🔢 Generate 6-Digit Cryptographic Code]
    GEN --> SAVE[Save Hashed OTP in Session / Cache (TTL: 5 min)]
    SAVE --> SEND[📧 Dispatch Email via Mailer Service]
    SEND --> PROMPT[Prompt User for 6-Digit Code]
    
    PROMPT --> VERIFY{Verify Input vs Stored OTP}
    VERIFY -- Correct & Not Expired --> PASS[✅ Authenticated as Student]
    VERIFY -- Incorrect Code --> INC[Increment Failed Attempts]
    VERIFY -- Expired --> EXP[Code Expired: Request New Code]
```
