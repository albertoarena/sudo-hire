<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The error kind of the last read of each company, next to its message:
 * certificate, http, parse or other (App\Sources\RunErrorKind).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ats_companies', function (Blueprint $table) {
            $table->string('last_error_kind')->nullable()->after('last_error');
        });
    }

    public function down(): void
    {
        Schema::table('ats_companies', function (Blueprint $table) {
            $table->dropColumn('last_error_kind');
        });
    }
};
