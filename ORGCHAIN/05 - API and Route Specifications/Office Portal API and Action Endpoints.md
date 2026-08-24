---
title: Office Portal API & Action Endpoints
tags: [api, office, actions, endpoints]
created: 2026-08-20
---

# 🏢 Office Portal API & Action Endpoints

- `POST /office-desk/activities`: Creates a new in-campus or off-campus submission draft.
- `PUT /office-desk/activities/{id}`: Updates activity rationale, schedule, and uploaded checklist files.
- `POST /office-desk/budget-utilization/receipt-reviews`: Submits a scanned receipt for OCR review.
- `POST /office-desk/archive/folders`: Creates a new semester folder in the compliance vault.
- `POST /office-desk/archive/documents`: Uploads files (`pdf`, `docx`, `xlsx`) into a designated archive folder.
