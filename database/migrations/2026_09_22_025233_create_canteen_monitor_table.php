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
        Schema::create('canteen_monitor', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('canteen_id');
            $table->unsignedBigInteger('monitor_id');
            $table->foreign('canteen_id')->references('id')->on('canteens')->cascadeOnDelete();
            $table->foreign('monitor_id')->references('id')->on('monitors')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canteen_monitor');
    }
};
