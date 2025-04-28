<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_tracks', function (Blueprint $table) {
            $table->id();
            $table->string('job_id')->index();
            $table->string('queue')->nullable();
            $table->string('job_class')->nullable(); 
            $table->json('payload')->nullable();
            $table->enum('status', ['queued', 'processing', 'failed', 'succeeded', 'retrying'])->default('queued');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('exception')->nullable();
            $table->float('execution_time')->nullable()->comment('in seconds');
            $table->integer('max_execution_time')->nullable()
                ->comment('Maximum allowed execution time in seconds');
            $table->integer('attempts')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_tracks');
    }
};