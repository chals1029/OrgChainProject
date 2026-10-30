---
title: Web & Portal Route Map
tags: [api, routes, routing, controllers]
created: 2026-08-20
---

# Web and Portal Route Map

Routes below are a focused map of current `routes/web.php`, not a claim that middleware alone grants access. SO organization binding, report publication/state, post audience and TOSA clearance are enforced by controllers/services.

| Method | URI Path | Middleware | Controller & Action | Route Name |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/` | `web` | `view('welcome')` | `welcome` |
| `POST` | `/student/login/code` | `web` | `StudentAuthController@sendCode` | `student.code.send` |
| `POST` | `/student/login/verify`| `web` | `StudentAuthController@verifyCode` | `student.code.verify` |
| `POST` | `/student/logout` | `web` | `StudentAuthController@logout` | `student.logout` |
| `GET` | `/student/auth/google` | `web` | `StudentAuthController@redirectToGoogle` | `student.auth.google` |
| `GET` | `/student/auth/google/callback` | `web` | `StudentAuthController@handleGoogleCallback` | `student.auth.google.callback` |
| `GET/POST`| `/orgchain-office-access-...` | `web` | `OfficeAuthController@showLogin / login` | `office.login` |
| `POST` | `/office/logout` | `web` | `OfficeAuthController@logout` | `office.logout` |
| `GET` | `/portal` | `student.auth` | `StudentPortalController@home` | `portal.home` |
| `GET` | `/portal/community` | `student.auth` | `StudentPortalController@community` | `portal.community` |
| `POST` | `/portal/community/posts` | `student.auth` | `CommunityFeedController@store` | `portal.community.posts.store` |
| `POST` | `/portal/community/posts/{post}/like` | `student.auth` | `CommunityFeedController@like` | `portal.community.posts.like` |
| `POST` | `/portal/community/posts/{post}/comments` | `student.auth` | `CommunityFeedController@comment` | `portal.community.posts.comment` |
| `GET` | `/portal/community/posts/{post}/image` | `student.auth` + audience | `CommunityFeedController@image` | `portal.community.posts.image` |
| `GET` | `/portal/community/posts/{post}/likers` | `student.auth` + audience | `CommunityFeedController@likers` | `portal.community.posts.likers` |
| `POST` | `/portal/community/comments/{comment}/like` | `student.auth` + parent audience | `CommunityFeedController@likeComment` | `portal.community.comments.like` |
| `DELETE` | `/portal/community/posts/{post}` | `student.auth` | `CommunityFeedController@destroy` | `portal.community.posts.destroy` |
| `PUT` | `/portal/profile` | `student.auth` (write denied: official records read-only) | `StudentPortalController@updateProfile` | `portal.profile.update` |
| `POST` | `/portal/feedback` | `student.auth` | `StudentPortalController@storeFeedback` | `portal.feedback.store` |
| `GET` | `/portal/expenses/{receipt}/verify` | `student.auth` + eligible receipt | `StudentPortalController@verifyExpenseSeal` | `portal.expenses.verify` |
| `GET` | `/office-desk` | `office.auth` | `OfficePortalController@dashboard` | `office.home` |
| `GET` | `/office-desk/analytics` | `office.auth` | `OfficePortalController@analytics` | `office.analytics` |
| `GET` | `/office-desk/analytics/export` | `office.auth` | `OfficePortalController@exportAnalytics` | `office.analytics.export` |
| `GET` | `/office-desk/activities` | `office.auth` | `OfficePortalController@activities` | `office.activities` |
| `GET` | `/office-desk/activities/create` | `office.auth` | `OfficePortalController@createActivity` | `office.activities.create` |
| `GET` | `/office-desk/activities/templates/download` | `office.auth` | `OfficePortalController@downloadActivityTemplates` | `office.activities.templates.download` |
| `POST` | `/office-desk/activities` | `office.auth` | `OfficePortalController@storeActivity` | `office.activities.store` |
| `GET` | `/office-desk/activities/{submission}/edit` | `office.auth` | `OfficePortalController@editActivity` | `office.activities.edit` |
| `PUT` | `/office-desk/activities/{submission}` | `office.auth` | `OfficePortalController@updateActivity` | `office.activities.update` |
| `DELETE` | `/office-desk/activities/{submission}/attachments/{key}` | `office.auth` | `OfficePortalController@deleteAttachment` | `office.activities.attachments.destroy` |
| `GET` | `/office-desk/calendar` | `office.auth` | `OfficePortalController@calendar` | `office.calendar` |
| `GET` | `/office-desk/budget-utilization` | `office.auth` | `OfficePortalController@budget` | `office.budget` |
| `POST` | `/office-desk/budget-utilization/receipts/validate-document` | `office.auth` + SO only + throttle | `ReceiptDocumentValidationController` | `office.budget.receipts.validate-document` |
| `POST` | `/office-desk/budget-utilization/receipt-reviews` | `office.auth` + SO only | `OfficePortalController@storeReceiptReview` | `office.budget.receipts.store` |
| `GET` | `/office-desk/budget-utilization/receipts/{review}/view` | `office.auth` | `OfficePortalController@viewReceipt` | `office.budget.receipts.view` |
| `GET` | `/office-desk/financial-report` | `office.auth` + SO/OSO only | `OfficePortalController@financial` | `office.financial` |
| `GET` | `/office-desk/accomplishment-report` | `office.auth` + SO/OSO only | `OfficePortalController@accomplishment` | `office.accomplishment` |
| `GET` | `/office-desk/reports` | `office.auth` + OSO only | `OfficePortalController@semesterReportDesk` | `office.reports.index` |
| `POST` | `/office-desk/reports/{reportType}/documents` | `office.auth` + SO only | `OfficePortalController@storeReportDocument` | `office.reports.documents.store` |
| `GET` | `/office-desk/reports/documents/{document}/view` | `office.auth` + SO/OSO only | `OfficePortalController@viewReportDocument` | `office.reports.documents.view` |
| `GET` | `/office-desk/reports/documents/{document}/workbook` | `office.auth` + OSO only | `OfficePortalController@previewReportWorkbook` | `office.reports.documents.workbook` |
| `POST` | `/office-desk/reports/{reportType}/submit` | `office.auth` + SO only, `ar`/`fr` | `OfficePortalController@submitSemesterReport` | `office.reports.submit` |
| `POST` | `/office-desk/reports/{reportType}/review` | `office.auth` + OSO only, `ar`/`fr` | `OfficePortalController@reviewSemesterReport` | `office.reports.review` |
| `POST` | `/office-desk/reports/{reportType}/submission-lock` | `office.auth` + OSO only, `ar`/`fr` | `ReportSubmissionWindowController@update` | `office.reports.submission-lock` |
| `POST` | `/office-desk/financial-report/reports` | `office.auth` + assigned SO | `SoFinancialReportController@store` | `office.financial.reports.store` |
| `GET` | `/office-desk/financial-report/reports/{document}/preview` | `office.auth` + assigned SO | `SoFinancialReportController@preview` | `office.financial.reports.preview` |
| `POST` | `/office-desk/financial-report/reports/{document}/submit` | `office.auth` + assigned SO | `SoFinancialReportController@submit` | `office.financial.reports.submit` |
| `GET` | `/office-desk/updates` | `office.auth` | `OfficePortalController@updates` | `office.updates` |
| `POST` | `/office-desk/updates/announcements` | `office.auth` + OSO only | `OfficePortalController@storeAnnouncement` | `office.updates.announcements.store` |
| `POST` | `/office-desk/updates/announcements/{announcement}/acknowledge` | `office.auth` + active actor | `OfficePortalController@acknowledgeAnnouncement` | `office.updates.announcements.acknowledge` |
| `GET` | `/office-desk/updates/announcements/{announcement}/attachment` | `office.auth` | `OfficePortalController@downloadAnnouncementAttachment` | `office.updates.announcements.attachment` |
| `POST` | `/office-desk/updates/templates` | `office.auth` + OSO only | `OfficePortalController@storeTemplate` | `office.updates.templates.store` |
| `POST` | `/office-desk/settings/logo` | `office.auth` + OSO only | `OfficePortalController@storeOsoLogo` | `office.settings.logo` |
| `GET` | `/office-desk/renewal` | `office.auth` | `OfficePortalController@renewal` | `office.renewal` |
| `POST` | `/office-desk/renewal/window` | `office.auth` | `OfficePortalController@updateRenewalWindow` | `office.renewal.window` |
| `POST` | `/office-desk/renewal/submit` | `office.auth` | `OfficePortalController@storeRenewalSubmission` | `office.renewal.submit` |
| `POST` | `/office-desk/renewal/information` | `office.auth` + assigned SO | `OrgRenewalInformationController@save` | `office.renewal.information.save` |
| `POST` | `/office-desk/renewal/submissions/{submission}/review` | `office.auth` + OSO only | `OfficePortalController@reviewRenewalSubmission` | `office.renewal.review` |
| `GET` | `/portal/tosa/templates/{key}` | `student.auth` | `StudentPortalController@downloadTosaTemplate` | `portal.tosa.templates.show` |
| `GET` | `/portal/tosa/documents/{key}` | `student.auth` + own packet | `StudentPortalController@downloadTosaDocument` | `portal.tosa.documents.show` |
| `POST` | `/portal/tosa/submit` | `student.auth` + ownership/lock/completeness | `StudentPortalController@submitTosaApplication` | `portal.tosa.submit` |
| `POST` | `/portal/activities/{activity}/rsvp` | `student.auth` | `StudentPortalController@toggleRsvp` | `portal.activities.rsvp` |
| `GET` | `/office-desk/student-reports` | `office.auth` + OSO only | `OfficePortalController@studentReports` | `office.student-reports` |
| `POST` | `/office-desk/student-reports/{report}/review` | `office.auth` + OSO only | `OfficePortalController@reviewStudentReport` (acknowledge/dismiss) | `office.student-reports.review` |
| `GET` | `/office-desk/tosa` | `office.auth` + TOSA clearance 1+ | `OfficePortalController@tosa` | `office.tosa` |
| `GET` | `/office-desk/tosa/{applicant}/documents/{key}` | `office.auth` + TOSA clearance/submitted dossier | `OfficePortalController@downloadTosaDocument` | `office.tosa.documents.download` |
| `POST` | `/office-desk/tosa/{applicant}/subsection` | `office.auth` + OSO | `OfficePortalController@updateTosaSubsection` | `office.tosa.subsection` |
| `POST` | `/office-desk/tosa/{applicant}/top-ten` | `office.auth` + OSO | `TosaShortlistController@add` | `office.tosa.top10.add` |
| `GET` | `/office-desk/tosa/{applicant}/package` | `office.auth` + OSO | `TosaPackageController@__invoke` | `office.tosa.package.download` |
| `POST` | `/office-desk/tosa/top-ten` | `office.auth` + OSO | `TosaShortlistController@save` | `office.tosa.top10.save` |
| `POST` | `/office-desk/tosa/references/template` | `office.auth` + OSO Master | `OfficePortalController@storeTosaReferenceTemplate` | `office.tosa.references.store` |
| `GET` | `/office-desk/archive` | `office.auth` | `OfficePortalController@archive` | `office.archive` |
| `POST` | `/office-desk/archive/folders` | `office.auth` + SO/OSO, organization-scoped | `OfficePortalController@storeArchiveFolder` | `office.archive.folders.store` |
| `POST` | `/office-desk/archive/documents` | `office.auth` + SO/OSO, folder-scoped | `OfficePortalController@storeArchiveDocument` | `office.archive.documents.store` |
| `GET` | `/office-desk/archive/documents/{document}/view` | `office.auth` + SO/OSO, ownership-checked | `OfficePortalController@viewArchiveDocument` | `office.archive.documents.view` |
| `GET` | `/office-desk/archive/documents/{document}/download` | `office.auth` + SO/OSO, ownership-checked | `OfficePortalController@downloadArchiveDocument` | `office.archive.documents.download` |
| `GET/POST` | `/voting-system/{segment}` | `web` | `VotingKernel@handle` | `voting.any` |

> Renewal: SO + OSO only. Window open/close is **OSO-only**. See [[Organization Renewal Filing Window]].

> Activity template preview: `preview=1` with a single `file` opens the designated preview (including the packaged WPCF PDF); without `preview=1`, the route retains the explicit original-template download path.

## Behavioral boundaries

- Student Settings has no Compact view. Budget breakdown is actual final-approved In-Campus/Off-Campus spending/allocation; see [[Transparency and Budget Visibility]].
- `/portal?announcement={id}` and `/office-desk/updates?announcement={id}` open that persisted notice. Student publication filters derive from stored type, not body keywords; office **Copy Link** copies the real notice URL.
- Office notice acknowledgment is idempotent per office account and retains its timestamp. Its additive table is absent from the current local ERD export even though deployed on VPS; this map does not apply migrations.
- Feedback review uses `decision=acknowledged|dismissed`; acknowledged feedback retains historical `verified` fields internally. Only the submitting student sees its history/acknowledgment notification at `/portal#feedback-{id}`. Receipt/report verification is unchanged.
- Community photos require an active student and the post's audience on every response; new uploads are private, existing photos use the same authorized route, and direct community-storage URLs are denied. Search/counts/seven reactions: [[Community Feed Posts and Likes]].
- AR and FR submit/review independently through `{reportType}=ar|fr`, not obsolete `/reports/semester/...` pair routes. Dedicated lock dialog, confirmed reopen, file gates and review permissions: [[Semester AR and FR Submission Workflow]].
- Student TOSA opens at `/portal#tosa`. Files are submitted together; OSO can review them or return the packet for correction. A manually chosen Top 10 stays private until OSO publishes it; the system does not score or rank applicants. See [[TOSA Application and Review Workflow]].
- Business mutations require shared confirmation without duplicating dedicated dialogs. Read-only actions and local file selection/cancellation do not prompt; logo/template choices require explicit confirmed upload/save. See [[Office Portal API and Action Endpoints]].

Source: `routes/web.php`, current README/CHANGELOG and the controller/service evidence linked from each workflow. Role matrix: [[Multi-Tier Office Roles SO OSO SDO OVCAA]]. Archive: [[Document Archive and Compliance Repository]].
