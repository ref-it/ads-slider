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
        Schema::table('canteens', function (Blueprint $table) {
            // stw-thueringen.de uses a separate resources_id per language
            // edition of the same physical canteen (e.g. Mensa Ehrenberg is
            // 46 on the German site, 597 on the English one). Optional,
            // since not every canteen necessarily has an English edition.
            $table->unsignedInteger('external_id_en')->nullable()->after('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('canteens', function (Blueprint $table) {
            $table->dropColumn('external_id_en');
        });
    }
};
