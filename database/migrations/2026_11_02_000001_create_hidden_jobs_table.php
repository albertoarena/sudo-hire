<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tombstones of hidden jobs: one row for each source key of the job, with its fingerprint.
 * A job with one of these keys or this fingerprint is not stored again. No retention in v1.
 * job_id has no foreign key: the row stays when retention deletes the job.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hidden_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id')->index();
            $table->string('source');
            $table->string('source_job_id');
            $table->string('fingerprint')->index();
            $table->timestamps();

            $table->index(['source', 'source_job_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hidden_jobs');
    }
};
