<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // RFC 5545 recurrence pattern only (FREQ/INTERVAL/BYDAY/...),
            // without DTSTART/UNTIL: the existing start/end columns remain
            // the single source of truth for the recurrence's time window.
            $table->text('rrule')->nullable()->after('repeat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('rrule');
        });
    }
};
