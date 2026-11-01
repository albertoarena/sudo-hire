<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The summary notifications that were sent. The scan reads the last sent_at
 * for the notification interval. M7 and M8 read it too.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sent_notifications', function (Blueprint $table) {
            $table->id();
            $table->timestamp('sent_at')->index();
            $table->unsignedInteger('job_count');
            $table->text('text');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sent_notifications');
    }
};
