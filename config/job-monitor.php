<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Maximum Execution Time
    |--------------------------------------------------------------------------
    |
    | This is the default maximum execution time (in seconds) for jobs that
    | use the TracksQueueJobs trait. Jobs can override this by setting
    | their own maxExecutionTime property.
    |
    */
    'default_max_execution_time' => env('JOB_MONITOR_DEFAULT_MAX_TIME', 300), // 5 minutes

    /*
    |--------------------------------------------------------------------------
    | Auto Cleanup Settings
    |--------------------------------------------------------------------------
    |
    | Configure automatic cleanup of old job tracking records
    |
    */
    'enable_auto_cleanup' => env('JOB_MONITOR_ENABLE_CLEANUP', true),
    'cleanup_days' => env('JOB_MONITOR_CLEANUP_DAYS', 10),
    'auto_cleanup_successful_jobs' => env('JOB_MONITOR_AUTO_CLEANUP_SUCCESSFUL', false),

    /*
    |--------------------------------------------------------------------------
    | Slack Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure how stuck job notifications are sent to Slack
    |
    */
    'slack' => [
        'enabled' => env('JOB_MONITOR_SLACK_ENABLED', false),
        'webhook_url' => env('JOB_MONITOR_SLACK_WEBHOOK_URL'),
        'channel' => env('JOB_MONITOR_SLACK_CHANNEL', '#alerts'),
        'username' => env('JOB_MONITOR_SLACK_USERNAME', 'Job Monitor'),
        'message' => env('JOB_MONITOR_SLACK_MESSAGE', '⚠️ *:app_name - :environment*: Found *:count* stuck jobs that exceeded their time limits.'),
        'max_jobs_in_notification' => env('JOB_MONITOR_SLACK_MAX_JOBS', 10),
        'notifiable_class' => \Illuminate\Notifications\AnonymousNotifiable::class,
    ]
];