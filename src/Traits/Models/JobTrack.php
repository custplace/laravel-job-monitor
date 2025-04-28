<?php

namespace Custplace\JobMonitor\Models;

use Illuminate\Database\Eloquent\Model;

class JobTrack extends Model
{
    protected $table = 'job_tracks';
    
    protected $fillable = [
        'job_id',
        'queue',
        'job_class',
        'payload',
        'status',
        'started_at',
        'finished_at',
        'exception',
        'execution_time',
        'max_execution_time',
        'attempts'
    ];

    protected $casts = [
        'payload' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'execution_time' => 'float',
        'max_execution_time' => 'integer',
        'attempts' => 'integer'
    ];

    /**
     * Check if this job has exceeded its maximum execution time
     */
    public function hasExceededMaxTime(): bool
    {
        if (!$this->started_at || !$this->max_execution_time || $this->status !== 'processing') {
            return false;
        }

        $runningTime = now()->diffInSeconds($this->started_at);
        return $runningTime > $this->max_execution_time;
    }

    /**
     * Get the current running time in seconds
     */
    public function getRunningTimeAttribute(): float
    {
        if (!$this->started_at) {
            return 0;
        }

        if ($this->finished_at) {
            return $this->finished_at->diffInSeconds($this->started_at);
        }

        return now()->diffInSeconds($this->started_at);
    }

    /**
     * Get the percentage of max execution time used
     */
    public function getTimePercentageAttribute(): float
    {
        if (!$this->max_execution_time || !$this->started_at) {
            return 0;
        }

        return min(100, ($this->running_time / $this->max_execution_time) * 100);
    }
}