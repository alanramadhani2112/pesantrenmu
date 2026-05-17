<?php

return [
    'assessment' => [
        'default_duration_days' => env('AKREDITASI_ASSESSMENT_DURATION_DAYS', 30),
    ],
    'visitasi' => [
        'default_duration_days' => env('AKREDITASI_VISITASI_DURATION_DAYS', 14),
    ],
    'reminder' => [
        'days_before_deadline' => env('AKREDITASI_REMINDER_DAYS_BEFORE', 3),
    ],
    'escalation' => [
        'interval_days' => env('AKREDITASI_ESCALATION_INTERVAL_DAYS', 1),
    ],
];
