---
name: Activity and audit system
description: Complete, privacy-aware traceability for sessions, business mutations and meaningful product interactions.
targets:
  - app/Models/ActivityEvent.php
  - app/Models/UserSession.php
  - app/Models/UiEvent.php
  - app/Observers/DomainActivityObserver.php
  - app/Http/Controllers/ActivityController.php
  - app/Http/Controllers/ActivityIngestionController.php
  - resources/js/app.js
  - resources/views/activity/index.blade.php
---

# Requirements

- Every successful business-data mutation produces one canonical server-side event with actor, entity, project/client context, channel, request correlation and sanitized before/after values.
- Task status, assignment, schedule and reorder events use specific event names instead of a generic update.
- Authentication records successful login, failed login, lockout, logout, revocation and session expiration without storing passwords or raw failed-login emails.
- A user session stores start, last interaction, end, active/idle seconds and a privacy-preserving device/IP description.
- Meaningful UI events are accepted only from an allowlist, in batches of at most 50, and never accept arbitrary event names or raw form contents.
- Browser heartbeat only counts active time while the page is visible and recently interacted with.
- Activity events are append-only in the model and database, and form a verifiable hash chain.
- Administrators and Direction can inspect all activity. Every other active role can inspect only their own activity.
- The activity center filters by dates, actor, event, project and channel, displays before/after changes, and exports the same authorized result set as CSV.
- Viewing and exporting the activity center are themselves audited.
- Project and task views show recent entity timelines.
- Retention is configurable by data class; a scheduled command purges expired UI/session detail while preserving canonical audit events for their longer window.
- The system never captures keystrokes, pointer coordinates, clipboard data, screenshots, passwords, tokens or unsaved form values.

[@test](../tests/Feature/ActivityCaptureTest.php)
[@test](../tests/Feature/ActivityCenterTest.php)
[@test](../tests/Feature/UserSessionTrackingTest.php)
[@test](../tests/Feature/ActivityRetentionTest.php)
