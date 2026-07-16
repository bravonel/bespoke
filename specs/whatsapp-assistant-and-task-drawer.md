---
name: WhatsApp assistant and persistent task drawer
description: Secure WhatsApp access to Bespoke OS operational guidance and uninterrupted checklist updates.
targets:
  - app/Http/Controllers/WhatsAppWebhookController.php
  - app/Services/WhatsApp/WhatsAppWebhookHandler.php
  - app/Services/WhatsApp/WhatsAppCloudApi.php
  - app/Http/Controllers/SubtaskController.php
  - resources/js/app.js
  - resources/views/tasks/_drawer.blade.php
---

# WhatsApp assistant

- Meta can verify the public webhook with a configured verification token.
- Incoming webhooks must pass the `X-Hub-Signature-256` HMAC check when an app secret is configured.
- Only active users with an enabled, normalized WhatsApp number can ask Bespoke OS questions.
- Repeated provider message IDs are idempotent and never trigger a second AI response.
- Text questions reuse the existing Bespoke OS assistant and return a concise WhatsApp response.
- Each authorized user carries up to six recent inbound/outbound messages as private conversational context for follow-up questions.
- Unknown, inactive, disabled, and unsupported contacts receive a safe response without operational data.
- Inbound and outbound messages are recorded with delivery state and linked to the internal user.
- AI context is restricted by role: privileged operational roles may use the global context; other users only see active project memberships, owned projects, and assigned tasks.
- The webhook acknowledges valid events even if one individual message cannot be processed, so Meta does not retry the complete payload indefinitely.

[@test](../tests/Feature/WhatsAppWebhookTest.php)
[@test](../tests/Feature/AiAssistantAuthorizationTest.php)

# Persistent task drawer

- Clicking a checklist item inside the task drawer updates it asynchronously.
- The drawer remains open after each successful update so several items can be changed in sequence.
- The completed counter and progress bar update from the server response.
- A failed update keeps the current drawer open and shows an inline error.
- Traditional non-JavaScript form submission remains supported.

[@test](../tests/Feature/ProjectBoardTest.php)
