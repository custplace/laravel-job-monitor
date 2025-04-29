<?php

namespace Custplace\JobMonitor\Notifications;

use Illuminate\Notifications\Notifiable;

class SlackNotifiable
{
    use Notifiable;

    /**
     * Route notifications to the Slack webhook URL from config.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string
     */
    public function routeNotificationForSlack($notification)
    {
        return config('job-monitor.slack.webhook_url');
    }
}