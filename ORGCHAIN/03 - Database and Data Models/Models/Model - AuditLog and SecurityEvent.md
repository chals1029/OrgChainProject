---
title: Model - AuditLog & SecurityEvent
tags: [models, security, forensics, audit]
created: 2026-08-20
---

# 🛡️ Model: AuditLog & SecurityEvent

### 📜 `AuditLog` (Native PDO)
- **Table**: `audit_logs`
- **Fields**: `id`, `user_id`, `actor_name`, `action`, `details`, `created_at`.

### 🚨 `SecurityEvent` (Native PDO)
- **Table**: `security_events`
- **Fields**: `id`, `ip_address`, `user_agent`, `method`, `path`, `event_type`, `severity` (`low`, `medium`, `high`, `critical`), `request_count`, `details`, `created_at`.
