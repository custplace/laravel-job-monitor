<?php

namespace Custplace\JobMonitor\Commands;

use Custplace\JobMonitor\Models\JobTrack;
use Custplace\JobMonitor\Notifications\JobExceededTimeLimit;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckStuckJobs extends Command
{
    protected $signature = 'job-monitor:check-stuck-jobs';

    protected $description = 'Check for jobs that have exceeded their execution time limit';

    public function handle()
    {
        $this->info('Checking for stuck jobs...');

        // Find jobs that are still processing but have exceeded their time limit
        $stuckJobs = JobTrack::where('status', 'processing')
            ->whereNotNull('started_at')
            ->whereNotNull('max_execution_time')
            ->get()
            ->filter(function ($job) {
                return $job->hasExceededMaxTime();
            });

        if ($stuckJobs->count() > 0) {
            $this->info('Found ' . $stuckJobs->count() . ' stuck jobs.');

            // Send Slack notification if enabled
            if (config('job-monitor.slack.enabled', false)) {
                // Create a new SlackNotifiable instance
                $notifiable = new \Custplace\JobMonitor\Notifications\SlackNotifiable();
                
                // Send the notification
                $notifiable->notify(new JobExceededTimeLimit($stuckJobs));
                $this->info('Notification sent to Slack about ' . $stuckJobs->count() . ' stuck jobs.');
            }

            // Print summary for console output
            $this->table(
                ['Job ID', 'Job Class', 'Queue', 'Started At', 'Running Time', 'Max Time', 'Time %'],
                $stuckJobs->map(function ($job) {
                    return [
                        $job->job_id,
                        $job->job_class,
                        $job->queue,
                        $job->started_at->format('Y-m-d H:i:s'),
                        $job->running_time . ' sec',
                        $job->max_execution_time . ' sec',
                        number_format($job->time_percentage, 1) . '%',
                    ];
                })
            );
        } else {
            $this->info('No stuck jobs found.');
        }

        // Clean up old records if enabled
        if (config('job-monitor.enable_auto_cleanup', true)) {
            $days = config('job-monitor.cleanup_days', 10);
            $deleted = JobTrack::query()
                ->where('status', '!=', 'processing')
                ->where('created_at', '<', now()->subDays($days))
                ->forceDelete();
                
            if ($deleted) {
                $this->info("Cleaned up {$deleted} job records older than {$days} days.");
            }
        }

        return 0;
    }
}