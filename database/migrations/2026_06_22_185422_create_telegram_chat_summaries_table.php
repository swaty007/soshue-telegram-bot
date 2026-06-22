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
        Schema::create('telegram_chat_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_chat_id')->index()->constrained()->cascadeOnDelete();
            $table->timestamp('period_started_at')->index();
            $table->timestamp('period_ended_at')->index();
            $table->unsignedInteger('message_count')->default(0);
            $table->string('prompt_fingerprint', 64);
            $table->text('summary')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['telegram_chat_id', 'period_ended_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_chat_summaries');
    }
};
