<?php

namespace Custplace\JobMonitor;

use Custplace\JobMonitor\Models\JobTrack;
use Custplace\JobMonitor\Traits\TracksQueueJobs;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\ServiceProvider;

class QueueTrackingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // When a job starts processing
        $this->app['events']->listen(JobProcessing::class, function (JobProcessing $event) {
            $job = $event->job;
            $payload = $job->payload();

            try {
                $command = unserialize($payload['data']['command']);

                // Only track jobs that use our trait
                if ($command && in_array(TracksQueueJobs::class, class_uses_recursive($command))) {
                    $jobId = $job->getJobId();

                    // Create or update the tracking record
                    JobTrack::updateOrCreate(
                        ['job_id' => $jobId],
                        [
                            'job_class' => get_class($command),
                            'queue' => $job->getQueue(),
                            'status' => 'processing',
                            'started_at' => now(),
                            'attempts' => $job->attempts(),
                            'max_execution_time' => $command->getMaxExecutionTime(),
                            'payload' => $command->getJobPayloadForTracking(),
                        ]
                    );
                }
            } catch (\Exception $e) {
                logger()->error('Error tracking job processing: ' . $e->getMessage(), [
                    'exception' => $e,
                    'job_id' => $job->getJobId(),
                ]);
            }
        });

        // When a job is successfully processed
        $this->app['events']->listen(JobProcessed::class, function (JobProcessed $event) {
            $job = $event->job;
            $jobId = $job->getJobId();

            try {
                // Update the job record using job_id
                $trackRecord = JobTrack::where('job_id', $jobId)->first();

                if ($trackRecord) {
                    $trackRecord->status = 'succeeded';
                    $trackRecord->finished_at = now();
                    $trackRecord->execution_time = now()->diffInSeconds($trackRecord->started_at);
                    $trackRecord->save();
                    
                    // Auto-cleanup if configured
                    if (config('job-monitor.auto_cleanup_successful_jobs', false)) {
                        $trackRecord->delete();
                    }
                }
            } catch (\Exception $e) {
                logger()->error('Error tracking job completion: ' . $e->getMessage(), [
                    'exception' => $e,
                    'job_id' => $jobId,
                ]);
            }
        });
        
        // When a job fails
        $this->app['events']->listen(JobFailed::class, function (JobFailed $event) {
            $job = $event->job;
            $jobId = $job->getJobId();
            $exception = $event->exception;

            try {
                // Update the job record using job_id
                $trackRecord = JobTrack::where('job_id', $jobId)->first();

                if ($trackRecord) {
                    $trackRecord->status = 'failed';
                    $trackRecord->finished_at = now();
                    $trackRecord->execution_time = now()->diffInSeconds($trackRecord->started_at);
                    $trackRecord->exception = $exception->getMessage() . "\n" . $exception->getTraceAsString();
                    $trackRecord->save();
                }
            } catch (\Exception $e) {
                logger()->error('Error tracking job failure: ' . $e->getMessage(), [
                    'exception' => $e,
                    'job_id' => $jobId,
                ]);
            }
        });
    }

    public function register()
    {
        //
    }
}