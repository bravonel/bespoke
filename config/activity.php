<?php

return [
    'audit_retention_days' => (int) env('ACTIVITY_AUDIT_RETENTION_DAYS', 730),
    // These windows identify records ready for a future verified archive.
    // The scheduled command never deletes raw telemetry.
    'session_retention_days' => (int) env('ACTIVITY_SESSION_RETENTION_DAYS', 730),
    'ui_retention_days' => (int) env('ACTIVITY_UI_RETENTION_DAYS', 730),
    'session_idle_minutes' => (int) env('ACTIVITY_SESSION_IDLE_MINUTES', 30),

    'ui_events' => [
        'dashboard.viewed',
        'project.viewed',
        'task.drawer_opened',
        'task.drawer_closed',
        'task.detail_viewed',
        'search.performed',
        'filter.applied',
        'modal.opened',
        'report.exported',
        'navigation.clicked',
    ],
];
