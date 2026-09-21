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
        Schema::table('monitors', function (Blueprint $table) {
            $table->boolean('show_weather_daily_forecast')->default(false)->after('show_weather_forecast')->comment('Indicates whether the multi-day weather outlook should be displayed on this monitor (DWD only)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn('show_weather_daily_forecast');
        });
    }
};
