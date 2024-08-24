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
        Schema::create('user_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guild_id');
            $table->foreign('guild_id')->references('id')->on('guilds')->onDelete('cascade');
            $table->json('queue');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_queues');
    }
};
