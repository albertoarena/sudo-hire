<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * True from the first save with a country until the next scan, the first scan. That scan
 * sends no notification and marks its matches as notified. Settings stored before this
 * migration keep false: their first scan has run.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('first_scan_pending')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('first_scan_pending');
        });
    }
};
