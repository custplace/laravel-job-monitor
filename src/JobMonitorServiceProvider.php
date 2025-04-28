<?php

namespace Custplace\JobMonitor;

use Illuminate\Support\ServiceProvider;
use Custplace\JobMonitor\Commands\CheckStuckJobs;

class JobMonitorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot()
    {
        // Register the queue tracking service provider
        $this->app->register(QueueTrackingServiceProvider::class);

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckStuckJobs::class,
            ]);
            
            // Publish config
            $this->publishes([
                __DIR__ . '/../config/job-monitor.php' => config_path('job-monitor.php'),
            ], 'job-monitor-config');
            
            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations/create_job_tracks_table.php' => database_path('migrations/' . date('Y_m_d_His') . '_create_job_tracks_table.php'),
            ], 'job-monitor-migrations');
        }

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/job-monitor.php', 'job-monitor'
        );
    }
}