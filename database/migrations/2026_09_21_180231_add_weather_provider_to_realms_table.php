<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->string('weather_provider')->nullable()->after('locale');
            $table->string('dwd_station_id')->nullable()->after('weather_provider');
        });

        // Preserve existing behavior: realms that already have OpenWeatherMap
        // configured keep working without the admin having to revisit the form.
        DB::table('realms')
            ->whereNotNull('ow_api_key')
            ->whereNotNull('ow_city_id')
            ->update(['weather_provider' => 'openweathermap']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->dropColumn(['weather_provider', 'dwd_station_id']);
        });
    }
};
