<?php

return [
    'resubmission_limit' => (int) env('AKREDITASI_RESUBMISSION_LIMIT', 3),
    'cooling_period_days' => (int) env('AKREDITASI_COOLING_PERIOD_DAYS', 30),

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
];
