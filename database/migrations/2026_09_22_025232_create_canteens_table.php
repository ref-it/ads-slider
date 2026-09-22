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
        Schema::create('canteens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('external_id')->comment('The resources_id used by the stw-thueringen.de Speiseplan endpoint');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('realm_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('realm_id')->references('id')->on('realms');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canteens');
    }
};
