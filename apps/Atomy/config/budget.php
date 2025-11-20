<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Budget Management Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the Nexus Budget package within
    | the Atomy application. These settings control alert thresholds,
    | variance investigation triggers, rollover policies, and system limits.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds (Percentage-Based)
    |--------------------------------------------------------------------------
    |
    | These thresholds determine when utilization alerts are triggered.
    | Values are percentages (0-100) of budget utilization.
    |
    */

    'alert_threshold_percentage' => env('BUDGET_ALERT_THRESHOLD', 80.0),

    'alert_critical_threshold' => env('BUDGET_ALERT_CRITICAL', 95.0),

    'alert_high_threshold' => env('BUDGET_ALERT_HIGH', 85.0),

    'alert_medium_threshold' => env('BUDGET_ALERT_MEDIUM', 75.0),

    'alert_low_threshold' => env('BUDGET_ALERT_LOW', 60.0),

    /*
    |--------------------------------------------------------------------------
    | Variance Investigation Threshold
    |--------------------------------------------------------------------------
    |
    | When budget variance (positive or negative) exceeds this percentage,
    | the system will automatically trigger an investigation workflow.
    |
    | Example: 15.0 = trigger investigation when variance is ±15% or more
    |
    */

    'variance_investigation_threshold_percentage' => env('BUDGET_VARIANCE_INVESTIGATION_THRESHOLD', 15.0),

    /*
    |--------------------------------------------------------------------------
    | Budget Hierarchy Settings
    |--------------------------------------------------------------------------
    |
    | Controls for hierarchical budget structures (parent-child relationships).
    |
    */

    'max_hierarchy_depth' => env('BUDGET_MAX_HIERARCHY_DEPTH', 5),

    /*
    |--------------------------------------------------------------------------
    | Budget Rollover Settings
    |--------------------------------------------------------------------------
    |
    | Controls automatic rollover behavior when fiscal periods close.
    |
    */

    'auto_rollover_enabled' => env('BUDGET_AUTO_ROLLOVER_ENABLED', true),

    // Default rollover policy for new budgets (expire, auto_roll, require_approval)
    'default_rollover_policy' => env('BUDGET_DEFAULT_ROLLOVER_POLICY', 'require_approval'),

    /*
    |--------------------------------------------------------------------------
    | Budget Approval Workflow Settings
    |--------------------------------------------------------------------------
    |
    | Controls when approval workflows are required for budget operations.
    |
    */

    // Minimum amount (in base currency) that requires approval for budget creation
    'approval_required_threshold' => env('BUDGET_APPROVAL_THRESHOLD', 50000.00),

    // Whether to require justification for budget amendments
    'require_justification_for_amendments' => env('BUDGET_REQUIRE_JUSTIFICATION', true),

    // Whether to require approval for budget transfers
    'require_approval_for_transfers' => env('BUDGET_REQUIRE_TRANSFER_APPROVAL', true),

    /*
    |--------------------------------------------------------------------------
    | Forecasting Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for AI-powered budget forecasting.
    |
    */

    // Number of historical periods to use for forecasting
    'forecast_lookback_periods' => env('BUDGET_FORECAST_LOOKBACK', 12),

    // Confidence interval percentage for forecasts (e.g., 95 = 95% confidence)
    'forecast_confidence_interval' => env('BUDGET_FORECAST_CONFIDENCE', 95),

    // Automatically generate forecasts when period opens
    'auto_generate_forecasts_on_period_open' => env('BUDGET_AUTO_FORECAST', true),

    /*
    |--------------------------------------------------------------------------
    | Simulation Mode Settings
    |--------------------------------------------------------------------------
    |
    | Controls for budget simulation (what-if analysis).
    |
    */

    // Maximum number of simulation scenarios per user
    'max_simulations_per_user' => env('BUDGET_MAX_SIMULATIONS', 10),

    // Number of days to retain simulation data
    'simulation_retention_days' => env('BUDGET_SIMULATION_RETENTION', 30),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Controls for budget analytics caching.
    |
    */

    // Cache TTL for consolidated budget analytics (in seconds)
    'analytics_cache_ttl' => env('BUDGET_ANALYTICS_CACHE_TTL', 3600), // 1 hour

    // Enable cache for budget analytics
    'analytics_cache_enabled' => env('BUDGET_ANALYTICS_CACHE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Revision History Settings
    |--------------------------------------------------------------------------
    |
    | Controls for budget revision tracking and retention.
    |
    */

    // Number of revisions to retain per budget (0 = unlimited)
    'max_revisions_per_budget' => env('BUDGET_MAX_REVISIONS', 0),

    // Number of days to retain revision history
    'revision_retention_days' => env('BUDGET_REVISION_RETENTION', 0), // 0 = unlimited

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Controls which channels to use for budget-related notifications.
    |
    */

    'notification_channels' => [
        'utilization_alert' => ['email', 'database'],
        'budget_exceeded' => ['email', 'database', 'slack'],
        'variance_investigation' => ['email', 'database'],
        'approval_required' => ['email', 'database'],
        'forecast_generated' => ['database'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Budget Override Settings
    |--------------------------------------------------------------------------
    |
    | Controls when users can override budget exceedance errors.
    |
    */

    // Maximum percentage over budget that can be overridden without approval
    'max_override_percentage' => env('BUDGET_MAX_OVERRIDE_PCT', 10.0),

    // Roles that can approve budget overrides
    'override_approval_roles' => ['CFO', 'Finance Manager', 'Budget Controller'],

    /*
    |--------------------------------------------------------------------------
    | Zero-Based Budgeting (ZBB) Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for Zero-Based Budgeting methodology support.
    |
    */

    // Enable ZBB methodology tracking
    'zbb_enabled' => env('BUDGET_ZBB_ENABLED', false),

    // Require justification for all ZBB budgets
    'zbb_require_justification' => env('BUDGET_ZBB_REQUIRE_JUSTIFICATION', true),

    /*
    |--------------------------------------------------------------------------
    | Performance Dashboard Settings
    |--------------------------------------------------------------------------
    |
    | Controls for manager performance scoring and dashboards.
    |
    */

    // Minimum number of periods required for performance scoring
    'min_periods_for_performance_score' => env('BUDGET_MIN_PERIODS_FOR_SCORE', 3),

    // Performance score thresholds (0-100 scale)
    'performance_score_gold_threshold' => env('BUDGET_PERFORMANCE_GOLD', 90),
    'performance_score_silver_threshold' => env('BUDGET_PERFORMANCE_SILVER', 75),
    'performance_score_bronze_threshold' => env('BUDGET_PERFORMANCE_BRONZE', 60),

];
