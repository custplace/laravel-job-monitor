<?php

namespace Custplace\JobMonitor\Notifications;

use Illuminate\Bus\Queueable;
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
        $count = $this->stuckJobs->count();
        
        $message = str_replace([':app_name', ':environment', ':count'], [$appName, $environment, $count], 
            config('job-monitor.slack.message', "⚠️ *{$appName} - {$environment}*: Found *{$count}* stuck jobs that exceeded their time limits."));
        
        $slackMessage = (new SlackMessage())
            ->from(config('job-monitor.slack.username', 'Job Monitor'))
            ->to(config('job-monitor.slack.channel'))
            ->image(config('job-monitor.slack.image', 'https://laravel.com/img/favicon/favicon-32x32.png'))
            ->warning()
            ->content($message);

        $job = $this->stuckJobs->first();

        return $slackMessage->attachment(function ($attachment) use ($job) {
            $attachment->title('Stuck Job Details')
                ->fields([
                    'Class' => $job->job_class,
                    'Started At' => $job->started_at->format('Y-m-d H:i:s'),
                    'Running Time' => \Carbon\Carbon::now()->diffInMinutes($job->started_at) . ' mins',
                ]);
        });
    }
}