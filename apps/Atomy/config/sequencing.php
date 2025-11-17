<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Sequence Configuration
    |--------------------------------------------------------------------------
    |
    | These settings apply to all sequences unless overridden individually.
    |
    */

    'defaults' => [
        'reset_period' => 'never',
        'step_size' => 1,
        'gap_policy' => 'allow_gaps',
        'overflow_behavior' => 'throw_exception',
        'exhaustion_threshold' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Reservation TTL
    |--------------------------------------------------------------------------
    |
    | Default time-to-live (in minutes) for number reservations.
    |
    */

    'reservation_ttl' => 30,

    /*
    |--------------------------------------------------------------------------
    | Predefined Sequences
    |--------------------------------------------------------------------------
    |
    | Define commonly used sequences here. These can be seeded or created
    | automatically on first use.
    |
    */

    'sequences' => [
        'invoice_number' => [
            'pattern' => 'INV-{YEAR}-{COUNTER:5}',
            'reset_period' => 'yearly',
            'gap_policy' => 'report_gaps_only',
        ],
        'purchase_order' => [
            'pattern' => 'PO-{YEAR}{MONTH}-{COUNTER:4}',
            'reset_period' => 'monthly',
            'gap_policy' => 'fill_gaps',
        ],
        'quotation' => [
            'pattern' => 'QT-{YEAR}-{COUNTER:5}',
            'reset_period' => 'yearly',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Enable/disable audit logging for sequence operations.
    |
    */

    'audit_enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Lock timeout and other performance-related settings.
    |
    */

    'lock_timeout_seconds' => 5,
];
