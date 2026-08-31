<?php

return [
    // Crew Hub runs a single approval gate: worker submits, the manager approves,
    // and the sheet is locked as fully approved. Client approval is retained in the
    // schema and workflow but switched off, so it can be re-enabled per deployment.
    'client_approval_enabled' => (bool) env('TIMESHEET_CLIENT_APPROVAL_ENABLED', false),

    'camp_sync' => [
        'connection' => env('CAMP_DB_CONNECTION', 'camp'),

        // Camp scopes a coordinator dashboard by camp + project. Everything the sync
        // imports is limited to this scope so unrelated camps never leak in.
        'camp_id' => (int) env('CAMP_SYNC_CAMP_ID', 28),
        'project_id' => (int) env('CAMP_SYNC_PROJECT_ID', 14),

        // Every company on the Camp coordinator page — both the prime/client table and
        // the subcontractor table — becomes a Crew Hub major project.
        //
        // These hierarchies mark the top of the tree. The unparented one identifies the
        // client that owns the dashboard, which becomes the single Crew Hub tenant.
        'root_hierarchies' => ['client', 'prime'],

        // Camp companies are owned by the user who created them. Only companies owned
        // by users holding this role are in scope, which mirrors what a Client Admin
        // sees on /scheduling/coordinator.
        'owner_role' => env('CAMP_SYNC_OWNER_ROLE', 'Client Admin'),

        // A tenant's roster is imported from its Camp bookings regardless of the payroll
        // week being synced, so a company whose schedule has already ended still sees its
        // people. This bounds how far back a booking may check out to stay in the roster.
        'roster_lookback_days' => (int) env('CAMP_SYNC_ROSTER_LOOKBACK_DAYS', 365),

        'regular_hours_per_day' => (float) env('TIMESHEET_REGULAR_HOURS_PER_DAY', 8),
        'travel_hours_per_day' => (float) env('TIMESHEET_TRAVEL_HOURS_PER_DAY', 1),
        'standby_hours_per_day' => (float) env('TIMESHEET_STANDBY_HOURS_PER_DAY', 8),
        'break_hours_per_work_day' => (float) env('TIMESHEET_BREAK_HOURS_PER_DAY', 0.5),
        'eligible_reservation_statuses' => [
            'pending',
            'arrivals',
            'check_in',
            'in_house',
            'check_out',
        ],
        'work_day_types' => [
            'camp',
            'loa',
            'local',
            'work day',
            'work from home',
        ],
        'travel_day_types' => ['travel day'],
        'standby_day_types' => ['on call'],
        'non_working_day_types' => [
            'off',
            'sick',
            'vacation',
            'no show',
            'no sleep',
            'available offsite',
        ],
    ],
];
