---
title: Web & Portal Route Map
tags: [api, routes, routing, controllers]
created: 2026-08-20
---

# 🌐 Web & Portal Route Map

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
| `DELETE` | `/portal/community/posts/{post}` | `student.auth` | `CommunityFeedController@destroy` | `portal.community.posts.destroy` |
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
| `POST` | `/office-desk/budget-utilization/receipts/scan` | `office.auth` + SO only + throttle | `ReceiptScanController` | `office.budget.receipts.scan` |
| `POST` | `/office-desk/budget-utilization/receipts/validate-document` | `office.auth` + SO only + throttle | `ReceiptDocumentValidationController` | `office.budget.receipts.validate-document` |
| `POST` | `/office-desk/budget-utilization/receipt-reviews` | `office.auth` + SO only | `OfficePortalController@storeReceiptReview` | `office.budget.receipts.store` |
| `GET` | `/office-desk/budget-utilization/receipts/{review}/view` | `office.auth` | `OfficePortalController@viewReceipt` | `office.budget.receipts.view` |
| `GET` | `/office-desk/financial-report` | `office.auth` + SO/OSO only | `OfficePortalController@financial` | `office.financial` |
| `GET` | `/office-desk/accomplishment-report` | `office.auth` + SO/OSO only | `OfficePortalController@accomplishment` | `office.accomplishment` |
| `POST` | `/office-desk/reports/{ar|fr}/documents` | `office.auth` + SO only | `OfficePortalController@storeReportDocument` | `office.reports.documents.store` |
| `GET` | `/office-desk/reports/documents/{document}/view` | `office.auth` + SO/OSO only | `OfficePortalController@viewReportDocument` | `office.reports.documents.view` |
| `POST` | `/office-desk/reports/semester/submit` | `office.auth` + SO only | `OfficePortalController@submitSemesterReports` | `office.reports.semester.submit` |
| `POST` | `/office-desk/reports/semester/review` | `office.auth` + OSO only | `OfficePortalController@reviewSemesterReports` | `office.reports.semester.review` |
| `GET` | `/office-desk/updates` | `office.auth` | `OfficePortalController@updates` | `office.updates` |
| `GET` | `/office-desk/renewal` | `office.auth` | `OfficePortalController@renewal` | `office.renewal` |
| `POST` | `/office-desk/renewal/window` | `office.auth` | `OfficePortalController@updateRenewalWindow` | `office.renewal.window` |
| `POST` | `/office-desk/renewal/submit` | `office.auth` | `OfficePortalController@storeRenewalSubmission` | `office.renewal.submit` |
| `POST` | `/office-desk/renewal/documents` | `office.auth` | `OfficePortalController@storeRenewalDocument` | `office.renewal.documents` |
| `POST` | `/office-desk/renewal/submissions/{submission}/review` | `office.auth` + OSO only | `OfficePortalController@reviewRenewalSubmission` | `office.renewal.review` |
| `POST` | `/portal/tosa/documents` | `student.auth` | `StudentPortalController@uploadTosaDocument` | `portal.tosa.documents.store` |
| `DELETE` | `/portal/tosa/documents/{key}` | `student.auth` | `StudentPortalController@removeTosaDocument` | `portal.tosa.documents.destroy` |
| `POST` | `/portal/tosa/submit` | `student.auth` | `StudentPortalController@submitTosaApplication` | `portal.tosa.submit` |
| `POST` | `/portal/activities/{activity}/rsvp` | `student.auth` | `StudentPortalController@toggleRsvp` | `portal.activities.rsvp` |
| `GET` | `/office-desk/student-reports` | `office.auth` + OSO only | `OfficePortalController@studentReports` | `office.student-reports` |
| `POST` | `/office-desk/student-reports/{report}/review` | `office.auth` + OSO only | `OfficePortalController@reviewStudentReport` | `office.student-reports.review` |
| `GET` | `/office-desk/tosa` | `office.auth` | `OfficePortalController@tosa` | `office.tosa` |
| `GET` | `/office-desk/archive` | `office.auth` | `OfficePortalController@archive` | `office.archive` |
| `POST` | `/office-desk/archive/folders` | `office.auth` + OSO only | `OfficePortalController@storeArchiveFolder` | `office.archive.folders.store` |
| `POST` | `/office-desk/archive/documents` | `office.auth` + OSO only | `OfficePortalController@storeArchiveDocument` | `office.archive.documents.store` |
| `ANY` | `/voting-system/{any}` | `web` | `VotingKernel@handle` | `voting.any` |

> Renewal: SO + OSO only. Window open/close is **OSO-only**. See [[Organization Renewal Filing Window]].

> Activity template preview: the templates route accepts `preview=1` with a single `file` and serves the original document inline for the in-app DOCX viewer; the same route without `preview=1` remains the explicit download path.
