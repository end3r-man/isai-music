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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->index();
            $table->string('discord_id')->index()->unique();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        Schema::create('guilds', function (Blueprint $table) {
            $table->id();
            $table->string('guild_id')->index();
            $table->string('owner_id')->index()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('guilds');
    }
};
