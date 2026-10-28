<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Job posts in the schema of docs/REQUIREMENTS.md section 5.
 * Eligibility and salary are flat columns, so filters can run in SQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('source_job_id');
            $table->text('source_url');
            $table->text('apply_url');
            $table->string('title');
            $table->string('company');
            $table->text('description')->nullable();
            $table->json('tags');
            $table->string('work_mode');
            $table->string('eligibility_type');
            $table->json('eligibility_regions');
            $table->json('eligibility_countries');
            $table->string('office_location')->nullable();
            $table->decimal('timezone_min', 4, 2)->nullable();
            $table->decimal('timezone_max', 4, 2)->nullable();
            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable();
            $table->char('salary_currency', 3)->nullable();
            $table->string('salary_period')->nullable();
            $table->text('salary_raw')->nullable();
            $table->string('contract_type');
            $table->string('seniority');
            $table->timestamp('posted_at');
            $table->timestamp('fetched_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('attribution');
            $table->string('fingerprint')->index();
            $table->timestamps();

            $table->unique(['source', 'source_job_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
