---
name: Audit integrity and safe deletion
description: Prevent domain deletion from mutating signed audit history, prefer reversible archival, and make chain verification complete and actionable.
targets:
  - app/Console/Commands/VerifyActivityChain.php
  - app/Http/Controllers/ActivityController.php
  - app/Http/Controllers/ClientController.php
  - app/Http/Controllers/ProjectController.php
  - app/Models/ActivityEvent.php
  - app/Models/Client.php
  - app/Models/Project.php
  - app/Services/Audit/AuditLogger.php
  - database/migrations
  - resources/views/activity/index.blade.php
  - resources/views/clients/index.blade.php
  - resources/views/projects/show.blade.php
---

# Outcome

Deleting or retiring operational records never changes previously signed audit events, never cascades through production history unexpectedly, and never leaves the integrity verifier unable to inspect later events.

# Root cause and legacy repair

- Historical audit events were signed while `activity_events.actor_id`, `project_id`, and `client_id` still used `nullOnDelete` foreign keys.
- Deleting projects caused MySQL to replace signed `project_id` values with `null`; the stored hashes and chain links were not changed.
- A controlled repair command identifies nullified context only when a unique original value can be proven by the existing HMAC. It never guesses or re-signs an event.
- Repair is dry-run by default and requires explicit apply and backup-confirmation flags.
- During an approved repair, only the update-protection trigger is removed, exact proven fields are restored, and the trigger is recreated in a guaranteed cleanup path.
- After repair, the complete chain must pass before integrity alerts can be resolved.
- The repair appends a new signed remediation event containing event IDs and restored context, without secrets or raw hashes.

# Immutable audit context

- Canonical audit events have no database foreign keys to users, sessions, projects, clients, tasks, subtasks, workloads, comments, brands, or other deletable domain records.
- Audit identifiers remain stored as immutable scalar historical references even when the referenced record no longer exists.
- Every new event stores signed, sanitized context snapshots sufficient to understand it after deletion: actor label, entity label/type, project code/name, and client name when available.
- Domain relationships remain optional conveniences for live display; rendering falls back to the signed snapshot when a live model is absent.
- Database triggers reject ordinary updates and deletes of canonical audit events on SQLite, MySQL/MariaDB, and PostgreSQL.

# Safe deletion policy

- Projects are archived reversibly instead of physically deleted through the web UI.
- Archived projects, tasks, workloads, comments, and historical activity remain available to authorized administrators and are excluded from normal active-work views by default.
- Clients with projects cannot be physically deleted. The UI deactivates them instead and preserves all projects and history.
- Brands referenced by projects cannot be physically deleted; they are deactivated instead.
- Physical deletion of an operational project/client/brand is not exposed through ordinary web routes.
- Child task/subtask deletion remains available where operationally required, but signed audit events and their context snapshots remain unchanged.
- Every archive, restore, deactivate, and blocked-deletion decision creates a canonical audit event.

# Complete verification and alerts

- Chain verification scans every event and reports all broken links and content-signature failures in one run.
- A failure in an early event never prevents inspection of later events.
- Alerts use one stable fingerprint per event and failure kind.
- Re-running verification reopens a resolved alert when its underlying failure still exists.
- An integrity alert cannot be manually resolved while its referenced event still fails verification.
- A stale alert may be resolved after the referenced event validates again; resolution itself is audited.
- The activity center clearly distinguishes a broken link, altered signed content, and a repaired legacy-context incident.
- Verification exits successfully only when the entire chain validates.

# Deployment and deletion checks

- Deployment verifies that the application path is the production release containing `.env`, not a source repository with fallback SQLite configuration.
- Production migrations must print the active driver/database and require an explicit confirmation that a backup exists.
- Regression tests cover deleting a project, client, user, task, and subtask after audit events exist and prove the full chain remains valid.
- Regression tests cover multiple simultaneous integrity failures, alert reopening, blocked premature resolution, and cryptographically proven legacy repair.
- The complete feature suite and production asset build pass before deployment.

[@test](../tests/Feature/Activity/ActivitySystemTest.php)
[@test](../tests/Feature/ActivityEventTest.php)
[@test](../tests/Feature/ProjectBoardTest.php)
