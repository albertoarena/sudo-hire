<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The company list of the ATS reader, with the last result of each company.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ats_companies', function (Blueprint $table) {
            $table->id();
            $table->string('ats');
            $table->string('slug');
            $table->string('display_name');
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->unsignedInteger('last_job_count')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['ats', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ats_companies');
    }
};
