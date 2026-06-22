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
        Schema::create('telegram_chats', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('telegram_id')->unique();
            $table->string('type', 32)->index();
            $table->string('title')->nullable();
            $table->string('username')->nullable()->index();
            $table->boolean('summaries_enabled')->default(true)->index();
            $table->boolean('reactions_enabled')->default(true);
            $table->timestamp('last_summary_at')->nullable()->index();
            $table->timestamps();

            $table->index(['summaries_enabled', 'last_summary_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_chats');
    }
};
