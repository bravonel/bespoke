---
name: Meeting P0 operational stabilization
description: Launch-blocking fixes for project workflow, shared assignments, catalog labels, brand persistence, and role-based administration.
targets:
  - app/Models/Project.php
  - app/Models/ProjectWorkload.php
  - app/Models/Task.php
  - app/Models/User.php
  - app/Services/Access/OperationalAccess.php
  - app/Services/Tasks/TaskWorkflow.php
  - app/Http/Controllers/CollaboratorController.php
  - app/Http/Controllers/ProjectController.php
  - app/Http/Controllers/TaskController.php
  - app/Http/Controllers/UserCapacityController.php
  - app/Support/OperationalLabels.php
  - resources/js/app.js
  - resources/views/collaborators/index.blade.php
  - resources/views/dashboard.blade.php
  - resources/views/projects/_client-brand-fields.blade.php
  - resources/views/projects/_workload-fields.blade.php
  - resources/views/projects/show.blade.php
---

# Outcome

Bespoke OS can be used in the next team pilot without false movement errors, lost brand selections, ambiguous workflow states, single-person area limits, or unauthorized collaborator/capacity changes.

# Task workflow

- The only user-selectable task statuses are `todo`, `in_progress`, `done`, and `finalized`, displayed as Por hacer, En proceso, Entregado, and Finalizado.
- Existing `blocked` tasks are migrated to `todo` without losing their `blocked_reason` or audit history.
- A task that cannot advance returns to Por hacer. Its blocker explanation remains visible on the shared card and task detail until the task advances again or an authorized user edits it.
- Entregado means the assignees finished their work and the task can wait for internal/client review.
- Finalizado means the task is approved, delivered to the client, and requires no more work.
- A task can only move to Finalizado from Entregado, and only a project manager/owner or globally authorized operational role can finalize it.
- Returning an Entregado or Finalizado task to an active status requires a correction/return reason.
- Dragging a card commits the status and order atomically. A successful response never shows a failure alert; a failed response restores the previous board state and does not leave a partial database move.
- Any active assignee on a shared task can move that task through the statuses they are authorized to operate.

[@test](../tests/Feature/TaskWorkflowTest.php)
[@test](../tests/Feature/ProjectBoardTest.php)

# Shared assignment and capacity

- A task is represented by exactly one card even when multiple people from the same area participate.
- A task supports multiple active assignees.
- Each assignee has an independent estimated-minute allocation on the shared task.
- The dashboard counts each person's own allocation, never the full shared-task total for every assignee and never an automatic equal split.
- The shared card and task detail show all active assignees.
- Existing single `assigned_to` values are migrated into the shared-assignment relation without losing access, ownership, personal work visibility, or capacity data.
- The project creator remains the single technical owner for authorization and audit purposes.
- The technical owner is not presented as the project's operational “Responsable”; visible operational responsibility comes from area/task assignments.
- Project creation and editing allow adding and removing multiple participant rows inside each area, with an individual date, hours, activity, and personal order for each person while preserving a single shared task when the activity is shared.

[@test](../tests/Feature/ProjectBoardTest.php)
[@test](../tests/Feature/ProjectMembershipTest.php)

# Areas, stages, and labels

- The operational areas available in project workload assignment are Cuentas, Medical, Diseño, Copy, Social Media, and Cliente.
- Project stages are Inicial, Cuentas, Medical, Diseño, Copy, Social Media, and Cliente.
- Existing project stages are migrated to the closest new stage without leaving an invalid stored value.
- Every user-facing occurrence of the operational area “Redacción” becomes “Copy”. Existing users stored with area `Redacción` are migrated to `Copy`.
- Medical job titles such as “Redactor médico” remain medical and are not renamed to Copy.
- Filters, select groups, cards, dashboard rows, exports, and collaborator views use the same canonical labels.

[@test](../tests/Feature/LocalizationTest.php)
[@test](../tests/Feature/ProjectBoardTest.php)

# Brand persistence

- A brand selected during project creation or editing is submitted and stored on the first attempt.
- Client changes clear the current brand only when that brand does not belong to the newly selected client.
- Re-rendering after validation errors and reopening the edit modal preserve the valid selected brand.
- A disabled or temporarily empty visual select cannot silently overwrite a previously valid `brand_id`.
- Server validation continues to reject brands that do not belong to the selected client.

[@test](../tests/Feature/ProjectBoardTest.php)

# Role-based administration

- Only active administrators can create, edit, deactivate, or reactivate collaborators and change collaborator roles.
- An administrator cannot deactivate their own account or the last active administrator.
- Only active Accounts users and administrators can change daily capacity or per-person workload hours.
- Other authenticated users receive HTTP 403 for these mutations even when they craft requests directly.
- The UI hides collaborator mutation controls and capacity/hour editing controls from unauthorized users; backend authorization remains authoritative.
- All successful collaborator, role, capacity, and assignment changes continue to emit canonical audit events.

[@test](../tests/Feature/UserRoleAdministrationTest.php)
[@test](../tests/Feature/CollaboratorManagementTest.php)
[@test](../tests/Feature/ProjectBoardTest.php)

# Compatibility and migration

- Migrations preserve existing projects, tasks, workload rows, timestamps, blocker explanations, and assignees.
- Legacy `blocked` requests are rejected after migration instead of silently recreating a removed status.
- Existing external integrations and AI summaries treat blocker explanations on Por hacer tasks as operational blockers without requiring a `blocked` status.
- No WhatsApp/email blocker notification behavior is added in this P0.
- The existing user-owned working-tree changes outside these targets remain untouched.

# Validation strategy

- Run all tests linked above plus the complete feature test suite.
- Add regression coverage for a card move that returns success after persistence, rollback on a downstream failure, first-attempt brand storage, multi-assignee access, per-person capacity math, blocked-task migration, and role-denied direct requests.
- Run the production asset build and verify the project creation/edit form, shared card, drag flow, collaborator controls, and dashboard at desktop and mobile breakpoints.
