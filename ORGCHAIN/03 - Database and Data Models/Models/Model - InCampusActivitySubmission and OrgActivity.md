---
title: Model - InCampusActivitySubmission & OrgActivity
tags: [models, activities, proposals]
created: 2026-08-20
---

# 📋 Model: InCampusActivitySubmission & OrgActivity

### 📝 `InCampusActivitySubmission` (Eloquent)
- **Table**: `in_campus_activity_submissions`
- **Identity / workflow**: `id`, `org_activity_id`, `status`, `activity_type` (`in_campus` or `local_off_campus`), `workflow_status`, `returned_to`, `organization_name`, `college`, `submitted_at`, `sla_due_at`, `reminder_sent_at`.
- **Activity narrative**: `rationale`, `objectives`, `participants`, `safety_plan`.
- **Legacy-compatible document columns**: the nullable `*_html` columns are retained for schema compatibility but are not used by the current filing form.
- **Evidence and review**: `attachments` (JSON), `document_statuses` (JSON).

Official templates are previewed from the project folders and completed files are uploaded through the checklist. The `*_html` columns are not a substitute for signed or scanned compliance uploads and are no longer written by the activity filing flow.

The `attachments` JSON payload stores the selected conditions plus uploaded file metadata, for example:

```json
{
  "conditions": {"medical": true},
  "programme": {
    "path": "in-campus-activities/123/programme.docx",
    "name": "Programme.docx",
    "uploaded_at": "2026-09-20T12:00:00+08:00"
  }
}
```

### 📎 `ActivityComplianceDoc` (Eloquent)
- **Table**: `activity_compliance_docs`
- **Purpose**: One review row per active checklist item after final submission.
- **Fields**: `id`, `org_activity_id`, `submission_id`, `doc_key`, `title`, `status` (`pending`, `approved`, `returned`), `returned_to`, `remarks`, timestamps.

### 📅 `OrgActivity` (Eloquent)
- **Table**: `org_activities`
- **Fields**: `id`, `title`, `description`, `status` (`draft`, `upcoming`, `ongoing`, `completed`), `workflow_status` (`created`, `college_review`, `oso_review`, `sdo_review`, `ovcaa_review`, `oc_approved`, `returned`), `returned_to`, `activity_scope`, `organization_name`, `college`, `program`, `starts_at`, `ends_at`, `approved_budget`, `implemented_budget`, `capacity`, `cover_image`.

Uploaded evidence files live on the Laravel public storage disk under `in-campus-activities/{activity_id}/` or `off-campus-activities/{activity_id}/`. The database keeps the metadata and review state; the original DOCX templates remain in the project folders and are not stored in the database by default.
