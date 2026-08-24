---
title: Security Incident & Rate Limit Response Runbook
tags: [operations, security, incidents, runbook]
created: 2026-08-20
---

# 🚨 Security Incident & Rate Limit Response Runbook

> [!warning] Incident Forensics
> When the system triggers alerts for SQL injection attempts, brute force attacks, or DDoS floods.

---

## 🔍 Step 1: Inspect Security Events
Run the following query in MySQL or Artisan Tinker:
```sql
SELECT event_type, severity, ip_address, method, path, request_count, details, created_at
FROM security_events
ORDER BY created_at DESC
LIMIT 25;
```

---

## 🛑 Step 2: Emergency IP Unbanning
If a legitimate user was accidentally blocked by the rate limiter:
- Clear application cache: `php artisan cache:clear`
- Delete the IP's rate limiter cache entry.
