<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Also on": the other sources of a job with the same fingerprint (REQUIREMENTS section 9).
 * Each row keeps its own links: an apply link is never replaced.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->cascadeOnDelete();
            $table->string('source');
            $table->string('source_job_id');
            $table->text('source_url');
            $table->text('apply_url');
            $table->string('attribution');
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->unique(['source', 'source_job_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_sources');
    }
};
