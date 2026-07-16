<?php

return [
    'audit_retention_days' => (int) env('ACTIVITY_AUDIT_RETENTION_DAYS', 730),
    'session_retention_days' => (int) env('ACTIVITY_SESSION_RETENTION_DAYS', 365),
    'ui_retention_days' => (int) env('ACTIVITY_UI_RETENTION_DAYS', 90),
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
