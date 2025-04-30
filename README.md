# Laravel Job Monitor

A Laravel package to monitor job execution and notify about stuck jobs.

## Installation

You can install the package via composer:

```bash
composer require custplace/laravel-job-monitor
```

The package will automatically register its service provider.

### Publishing the config and migrations

You can publish the config file and migrations with:

```bash
php artisan vendor:publish --provider="Custplace\JobMonitor\JobMonitorServiceProvider" --tag="job-monitor-config"
php artisan vendor:publish --provider="Custplace\JobMonitor\JobMonitorServiceProvider" --tag="job-monitor-migrations"
```

Run the migrations:

```bash
php artisan migrate
```

## Configuration

After publishing the config file, you can find it at `config/job-monitor.php`. Here you can configure:

- Default maximum execution time for jobs
- Slack notification settings
- Auto cleanup settings for old job records
- Scheduling frequency

## Usage

### Add tracking to your jobs

To monitor a job, simply use the `TracksQueueJobs` trait in your job class:

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Custplace\JobMonitor\Traits\TracksQueueJobs;

class ProcessPodcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use TracksQueueJobs; // Add this trait
    
    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        // Optional: Set a custom max execution time (in seconds)
        // $this->setMaxExecutionTime(600); // 10 minutes
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Your job logic here
    }
    
    /**
     * Optional: Customize what data gets stored for tracking
     */
    public function getJobPayloadForTracking()
    {
        return [
            'custom_field' => 'custom value',
            // Don't include sensitive data!
        ];
    }
}
```
### Upadete service provider
Make sure the package's service provider is registered in config/app.php:

```php
'providers' => [
    // Other providers...
    Custplace\JobMonitor\JobMonitorServiceProvider::class,
],
```
### Check for stuck jobs

You can check for stuck jobs manually:

```bash
php artisan job-monitor:check-stuck-jobs
```

Or automatically by adding the command to your scheduler in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Check for stuck jobs every 5 minutes (or whatever frequency you prefer)
    $schedule->command('job-monitor:check-stuck-jobs')->everyTenMinutes();
}
```

### Configure Slack notifications

To enable Slack notifications, set the following environment variables:

```env
JOB_MONITOR_SLACK_ENABLED=true
JOB_MONITOR_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/TXXXXXXXX/BXXXXXXXX/XXXXXXXXXXXXXXXXXXXXXXXX
JOB_MONITOR_SLACK_CHANNEL=#alerts
```

## License

The MIT License (MIT).