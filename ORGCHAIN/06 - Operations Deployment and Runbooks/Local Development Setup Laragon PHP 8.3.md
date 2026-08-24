---
title: Local Development Setup (Laragon + PHP 8.3)
tags: [operations, setup, laragon, php, development]
created: 2026-08-20
---

# 🚀 Local Development Setup (Laragon + PHP 8.3)

> [!tip] Quick Start Guide
> Step-by-step instructions to get OrgChain running locally on Windows with Laragon and PHP 8.3.

---

## 🛠️ Step-by-Step Setup Instructions

### 1. Prerequisites
- **Laragon Full** with PHP 8.3+ and MySQL 8.0+ / MariaDB.
- **Node.js 18+** and NPM.
- **Composer 2.x**.

### 2. Configure Environment File
```powershell
cp .env.example .env
php artisan key:generate
```

Ensure your `.env` database parameters match your Laragon MySQL setup:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=votingsystem
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Install Dependencies
```powershell
composer install
npm install
npm run build
```

### 4. Run Migrations & Seeders
```powershell
php artisan migrate:fresh --seed
```

### 5. Start Development Server
```powershell
# Option A: Standalone PHP Server
php -S 127.0.0.1:8000 -t public public/server-router.php

# Option B: Standard Artisan Serve
php artisan serve --port=8000
```
