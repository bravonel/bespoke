# Modelo de datos V1

## 1. Objetivo

Definir las tablas principales para arrancar el MVP sin sobredisenar la base.

## 2. Entidades core

### users

- id
- name
- email
- phone
- password
- role
- status
- timezone
- last_seen_at

### clients

- id
- name
- status
- primary_contact_name
- primary_contact_email
- primary_contact_phone

### brands

- id
- client_id
- name
- category
- status

### teams

- id
- name
- status

### team_user

- team_id
- user_id

## 3. Modulo de proyectos

### project_templates

- id
- name
- project_type
- status

### project_template_steps

- id
- project_template_id
- name
- sort_order
- default_role
- sla_days

### projects

- id
- client_id
- brand_id
- project_template_id
- name
- code
- project_type
- priority
- status
- current_stage
- owner_id
- due_at
- started_at
- completed_at

### project_members

- project_id
- user_id
- role_on_project

### tasks

- id
- project_id
- parent_task_id
- title
- description
- stage
- priority
- status
- assigned_to
- created_by
- due_at
- completed_at
- blocked_by_task_id

### task_comments

- id
- task_id
- user_id
- body

### deliverables

- id
- project_id
- task_id
- name
- type
- version
- status
- uploaded_by
- due_at

### approvals

- id
- approvable_type
- approvable_id
- requested_by
- requested_to
- decision
- comment
- decided_at

## 4. Modulo de claims

### evidence_sets

- id
- client_id
- brand_id
- name
- therapeutic_area
- status
- owner_id

### evidence_documents

- id
- evidence_set_id
- title
- source_type
- source_reference
- file_path
- extracted_text_path
- extraction_status
- uploaded_by

### evidence_claims

- id
- evidence_set_id
- claim_text
- normalized_claim_text
- severity
- rationale
- source_document_id
- source_locator
- status
- approved_by
- approved_at

### evidence_references

- id
- evidence_set_id
- citation_text
- normalized_citation_text
- source_document_id
- page_locator

### materials

- id
- project_id
- evidence_set_id
- deliverable_id
- title
- version
- file_path
- extracted_text_path
- extraction_status
- uploaded_by

### review_runs

- id
- material_id
- status
- started_at
- finished_at
- summary
- overall_score
- semaphore
- triggered_by

### review_findings

- id
- review_run_id
- finding_type
- severity
- title
- detail
- expected_text
- found_text
- page_reference
- status

### finding_references

- id
- review_finding_id
- evidence_claim_id
- evidence_reference_id
- match_type
- confidence_score

## 5. Entidades compartidas

### files

- id
- fileable_type
- fileable_id
- disk
- path
- original_name
- mime_type
- size
- uploaded_by

### activities

- id
- actor_id
- subject_type
- subject_id
- event
- metadata
- created_at

### reminders

- id
- remindable_type
- remindable_id
- channel
- scheduled_for
- sent_at
- status

## 6. Relaciones clave

- un `client` tiene muchas `brands`
- un `brand` tiene muchos `projects`
- un `project` tiene muchas `tasks`, `deliverables` y `materials`
- un `evidence_set` tiene muchos `evidence_documents`, `evidence_claims` y `evidence_references`
- un `material` tiene muchas `review_runs`
- un `review_run` tiene muchos `review_findings`

## 7. Reglas de modelado

- usar `soft deletes` solo donde haya valor claro de recuperacion
- preferir enums para estados controlados
- guardar texto original y texto normalizado cuando haya matching
- registrar siempre `uploaded_by`, `created_by` o `triggered_by`
- mantener `activities` para trazabilidad operativa y regulatoria

## 8. Posibles tablas futuras

- capacity_snapshots
- client_portal_accesses
- notification_preferences
- ai_prompts
- ai_review_artifacts
- cofepris_submissions
