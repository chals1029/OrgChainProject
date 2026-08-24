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
| `GET` | `/office-desk/activities` | `office.auth` | `OfficePortalController@activities` | `office.activities` |
| `GET` | `/office-desk/activities/create` | `office.auth` | `OfficePortalController@createActivity` | `office.activities.create` |
| `POST` | `/office-desk/activities` | `office.auth` | `OfficePortalController@storeActivity` | `office.activities.store` |
| `GET` | `/office-desk/activities/{submission}/edit` | `office.auth` | `OfficePortalController@editActivity` | `office.activities.edit` |
| `PUT` | `/office-desk/activities/{submission}` | `office.auth` | `OfficePortalController@updateActivity` | `office.activities.update` |
| `GET` | `/office-desk/calendar` | `office.auth` | `OfficePortalController@calendar` | `office.calendar` |
| `GET` | `/office-desk/budget-utilization` | `office.auth` | `OfficePortalController@budget` | `office.budget` |
| `POST` | `/office-desk/budget-utilization/receipt-reviews` | `office.auth` | `OfficePortalController@storeReceiptReview` | `office.budget.receipts.store` |
| `GET` | `/office-desk/financial-report` | `office.auth` | `OfficePortalController@financial` | `office.financial` |
| `GET` | `/office-desk/accomplishment-report` | `office.auth` | `OfficePortalController@accomplishment` | `office.accomplishment` |
| `GET` | `/office-desk/updates` | `office.auth` | `OfficePortalController@updates` | `office.updates` |
| `GET` | `/office-desk/archive` | `office.auth` | `OfficePortalController@archive` | `office.archive` |
| `POST` | `/office-desk/archive/folders` | `office.auth` | `OfficePortalController@storeArchiveFolder` | `office.archive.folders.store` |
| `POST` | `/office-desk/archive/documents` | `office.auth` | `OfficePortalController@storeArchiveDocument` | `office.archive.documents.store` |
| `ANY` | `/voting-system/{any}` | `web` | `VotingKernel@handle` | `voting.any` |
