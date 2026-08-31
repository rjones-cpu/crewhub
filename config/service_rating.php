<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CH-11 Service Rating
    |--------------------------------------------------------------------------
    |
    | Working-default policy JSON ships with the package. Project-specific
    | active versions are stored in service_rating_policy_versions once seeded.
    |
    */
    'default_policy_path' => config_path('service_rating_policy_v1_working_default.json'),

    'default_policy_code' => 'LODGEX_CH11_V1_WORKING_DEFAULT',

    'default_time_zone' => env('APP_TIMEZONE', 'Australia/Perth'),

    /*
    | Rolling operational window in calendar days (package working default: 30).
    */
    'evaluation_window_days' => 30,

    /*
    | When no published snapshot exists yet, the dashboard calculator may run
    | live and optionally persist a snapshot so history starts from activation.
    */
    'auto_publish_live_calculations' => true,
];
