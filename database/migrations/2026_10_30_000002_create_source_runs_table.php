<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per source run (REQUIREMENTS section 9). The shares are from 0 to 1,
 * null when the run failed. Parsers are tuned from these numbers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->text('error')->nullable();
            $table->string('error_kind')->nullable();
            $table->unsignedInteger('items_fetched')->default(0);
            $table->unsignedInteger('items_skipped')->default(0);
            $table->double('unknown_eligibility_share')->nullable();
            $table->double('unknown_salary_share')->nullable();
            $table->timestamps();

            $table->index(['source', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_runs');
    }
};
