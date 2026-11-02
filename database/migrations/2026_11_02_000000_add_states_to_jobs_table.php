<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Job states (PRODUCT.md): seen or unseen, and one of saved, applied or hidden.
 * A job with no state is in the New list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->timestamp('seen_at')->nullable();
            $table->string('state')->nullable()->index();
            $table->timestamp('state_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['state']);
            $table->dropColumn(['seen_at', 'state', 'state_at']);
        });
    }
};
