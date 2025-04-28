<?php

namespace Custplace\JobMonitor\Traits;

use Custplace\JobMonitor\Models\JobTrack;

trait TracksQueueJobs
{
    // Default maximum execution time in seconds (can be overridden in jobs)
    protected $maxExecutionTime;

    public function __construct()
    {
        // Set default from config or fallback to 5 minutes
        $this->maxExecutionTime = config('job-monitor.default_max_execution_time', 300);
    }

    public function getMaxExecutionTime()
    {
        return $this->maxExecutionTime;
    }

    public function setMaxExecutionTime(int $maxExecutionTime): self
    {
        if ($maxExecutionTime < 0) {
            throw new \InvalidArgumentException('Maximum execution time must be a positive integer.');
        }

        $this->maxExecutionTime = $maxExecutionTime;
        return $this;
    }

    public function getJobPayloadForTracking()
    {
        // Override this in your job to customize what data gets stored
        // Default: empty to avoid storing sensitive data
        return [];
    }
}