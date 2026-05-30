<?php

return [
    // Banding (appeal) settings
    'banding_limit' => (int) env('AKREDITASI_BANDING_LIMIT', 1),
    'banding_review_days' => (int) env('AKREDITASI_BANDING_REVIEW_DAYS', 14),
    'banding_reminder_days_before' => (int) env('AKREDITASI_BANDING_REMINDER_DAYS', 3),

    // Rejection settings
    'rejection_limit' => (int) env('AKREDITASI_REJECTION_LIMIT', 3),
    'perbaikan_deadline_days' => (int) env('AKREDITASI_PERBAIKAN_DEADLINE_DAYS', 14),
    'perbaikan_reminder_days_before' => (int) env('AKREDITASI_PERBAIKAN_REMINDER_DAYS', 3),

    // Final rejection categories
    'final_rejection_categories' => [
        'nilai_tidak_memenuhi' => 'Nilai Tidak Memenuhi Standar',
        'laporan_tidak_lengkap' => 'Laporan Visitasi Tidak Lengkap',
        'kartu_kendali_tidak_sesuai' => 'Kartu Kendali Tidak Sesuai',
        'inkonsistensi_data' => 'Inkonsistensi Data',
        'lainnya' => 'Lainnya',
    ],

    // Concurrent access handling
    'polling_interval' => (int) env('AKREDITASI_POLLING_INTERVAL', 10),
    'presence_enabled' => (bool) env('AKREDITASI_PRESENCE_ENABLED', false),

    // Trash retention
    'trash' => [
        'retention_days' => (int) env('TRASH_RETENTION_DAYS', 90),
    ],
];
