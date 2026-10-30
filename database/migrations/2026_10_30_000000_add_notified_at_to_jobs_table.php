<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When a job was in a notification, so it is never notified twice (REQUIREMENTS section 9).
 * Retention reads fetched_at, the last time the job was seen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->timestamp('notified_at')->nullable()->index();
            $table->index('fetched_at');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['fetched_at']);
            $table->dropIndex(['notified_at']);
            $table->dropColumn('notified_at');
        });
    }
};
