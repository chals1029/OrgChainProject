---
title: Model - InCampusActivitySubmission & OrgActivity
tags: [models, activities, proposals]
created: 2026-08-20
---

# 📋 Model: InCampusActivitySubmission & OrgActivity

### 📝 `InCampusActivitySubmission` (Eloquent)
- **Table**: `in_campus_activity_submissions`
- **Fields**: `id`, `org_activity_id`, `status`, `activity_type`, `organization_name`, `rationale`, `objectives`, `participants`, `safety_plan`, `programme_html`, `project_proposal_html`, `budget_proposal_html`, `faculty_in_charge_html`, `attachments` (JSON), `submitted_at`.

### 📅 `OrgActivity` (Eloquent)
- **Table**: `org_activities`
- **Fields**: `id`, `title`, `description`, `status` (`upcoming`, `ongoing`, `completed`), `location`, `starts_at`, `ends_at`, `cover_image`.
