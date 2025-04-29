<?php

namespace Custplace\JobMonitor\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class JobExceededTimeLimit extends Notification
{
    use Queueable;

    /**
     * The collection of stuck jobs.
     */
    protected $stuckJobs;

    /**
     * Create a new notification instance.
     */
    public function __construct(Collection $stuckJobs)
    {
        $this->stuckJobs = $stuckJobs;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['slack'];
    }

    /**
     * Get the Slack representation of the notification.
     */
    public function toSlack($notifiable): SlackMessage
    {
        $appName = config('app.name');
        $environment = app()->environment();
        
        $message = (new SlackMessage())
            ->from(config('job-monitor.slack.username', 'Job Monitor'))
            ->to(config('job-monitor.slack.channel'))
            ->image(config('job-monitor.slack.image', 'https://laravel.com/img/favicon/favicon-32x32.png'))
            ->warning()
            ->content(config('job-monitor.slack.message', "⚠️ *{$appName} - {$environment}*: Found *{$this->stuckJobs->count()}* stuck jobs that exceeded their time limits."));

        // Add attachment with details for each stuck job
        $fields = [];
        
        foreach ($this->stuckJobs as $job) {
            $runningTime = $job->running_time;
            $maxTime = $job->max_execution_time;
            $percentage = number_format($job->time_percentage, 1);
            
            $fields[] = [
                'title' => class_basename($job->job_class),
                'value' => "Queue: {$job->queue}\nRunning: {$runningTime}s / {$maxTime}s ({$percentage}%)\nStarted: {$job->started_at->diffForHumans()}",
                'short' => true
            ];
        }
        
        // Limit the number of fields to avoid hitting Slack's message limits
        $maxJobsToDisplay = config('job-monitor.slack.max_jobs_in_notification', 10);
        $fields = array_slice($fields, 0, $maxJobsToDisplay);
        
        if ($this->stuckJobs->count() > $maxJobsToDisplay) {
            $extraCount = $this->stuckJobs->count() - $maxJobsToDisplay;
            $fields[] = [
                'title' => 'Additional stuck jobs',
                'value' => "Plus {$extraCount} more stuck jobs not shown here",
                'short' => false
            ];
        }
        
        return $message->attachment(function ($attachment) use ($fields) {
            $attachment->title('Stuck Job Details')
                ->fields($fields);
        });
    }
}