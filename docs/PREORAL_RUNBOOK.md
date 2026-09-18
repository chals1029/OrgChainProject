# Pre-oral OrgChain Desk Runbook

## Demo package (locked)

- Working approval pipeline: Created → College Review → OSO → SDO → OVCAA → OC Approved (with return targets)
- Printable Financial Report (`/office-desk/financial-report/print`)
- Student overview, SDG tags, program filter, feedback channel
- Calendar clicks + dashboard/analytics charts fed by `PreOralDemoSeeder`

## One-time setup

```bash
php artisan migrate
php artisan db:seed --class=PreOralDemoSeeder
php artisan storage:link
```

Log in via the office desk path from `.env` / `config/orgchain.php` (`office_login_path`). Use SO, OSO, SDO, and OVCAA accounts already in the office users table.

## End-to-end happy path (pre-oral script)

1. **SO** — Dashboard → confirm Total Funds / beginning balance editable → create or open an activity → Advance when at `created` / returned.
2. **College / OSO / SDO / OVCAA** — Dashboard workflow panel → Advance through the chain (or Return with a named target).
3. **OSO** — Urgency list → Email SO reminder; Proposals/compliance docs → approve/return per document.
4. **Budget** — Filter by organization → Record Expense with supplier → confirm receipt review row.
5. **Financial Report** — Fund source filter → Inflow vs Outflow chart → **Generate / Print Report** → Save as PDF from the browser.
6. **Accomplishment** — Filter male/female, SDG, core values.
7. **Analytics** — College performance, utilization %, approved vs implemented budget.
8. **Calendar** — Click a day with events → open activity detail / upcoming item.
9. **Student portal** — Overview + SDG highlights → filter upcoming by program → submit feedback → confirm OSO dashboard Student Voice list.
10. **Archive** — Open folder **per activity title**.
11. **TOSA** — Filter applicants by subsection; change subsection (persisted).

## Performance smoke notes

Use browser DevTools Network panel on:

- `/office-desk/`
- `/office-desk/analytics`
- `/office-desk/budget-utilization`
- `/office-desk/financial-report`
- `/portal/`

Optional CLI timing (Laragon Apache / PHP built-in):

```bash
curl -o NUL -s -w "home %{time_total}s\n" http://127.0.0.1/OrgChain/OrgChains/public/office-desk/
curl -o NUL -s -w "analytics %{time_total}s\n" http://127.0.0.1/OrgChain/OrgChains/public/office-desk/analytics
curl -o NUL -s -w "financial %{time_total}s\n" http://127.0.0.1/OrgChain/OrgChains/public/office-desk/financial-report
```

Target for demo: main HTML routes under ~2s on local Laragon. If charts are empty, re-run `PreOralDemoSeeder`.

## User training

Schedule **2–3 days before pre-oral**. Dry-run the happy path above with one SO and one OSO account. Fix only click/empty-data issues found in that session.

When the exact pre-oral date is confirmed, lock training as pre-oral minus 2 or 3 calendar days.

## Report template note

Print-ready HTML uses BatStateU letterhead-style layout. Swap later if an official FR/AR Word/PDF template is provided.
