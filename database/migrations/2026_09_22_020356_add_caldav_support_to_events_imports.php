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
        Schema::table('events_imports', function (Blueprint $table) {
            $table->string('source_type')->default('json')->after('import_url');
            $table->string('caldav_username')->nullable()->after('source_type');
            $table->text('caldav_password')->nullable()->after('caldav_username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events_imports', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'caldav_username', 'caldav_password']);
        });
    }
};
