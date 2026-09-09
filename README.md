# OrgChain

OrgChain is a blockchain-powered student organization management platform for Batangas State University, The National Engineering University.

It connects Student Organizations, the Office of Student Organizations (OSO), the Sustainable Development Office (SDO), and OVCAA through a trusted on-chain workflow for proposals, activities, budgets, and compliance.

## Modules

- **Student Organization desk** (`/office-desk`): dashboard, analytics, activities, in-campus activity workflow, calendar, budget utilization with receipt OCR review, financial report, accomplishment report, updates, archive, and TOSA.
- **Student portal** (`/portal`): home and community feed with posts, likes, and comments.
- **Voting system** (`/voting-system`): integrated official voting module with Google institutional authentication.

## Authentication

- Students: SR Code + verification code, plus Continue with Institutional Account (Google OAuth).
- Offices: BatStateU email + password on a private office login path (`OFFICE_LOGIN_PATH`).

## Tech stack

- [Laravel 13](https://laravel.com) + PHP 8.3
- MySQL: `votingsystem` and `orgchain` databases
- [Vite](https://vite.dev) + [Tailwind CSS](https://tailwindcss.com)
- [TinyMCE](https://www.tiny.cloud) for in-campus document editing
- [Tesseract.js](https://tesseract.projectnaptha.com) for local browser receipt OCR

## Local setup

```bash
composer install
npm install
```

Copy your environment file, then configure `DB_DATABASE`, `DB_ORGCHAIN_DATABASE`, Google OAuth, mail, and `TINYMCE_API_KEY`.

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

## Useful routes

- `/` — landing page
- `/portal` — student portal
- `/office-desk` — organization/office desk
- `/voting-system` — voting system

## License

MIT.
