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
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_chat_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('telegram_user_id')->index()->nullable()->constrained()->nullOnDelete();
            $table->bigInteger('telegram_message_id');
            $table->text('text')->nullable();
            $table->json('payload');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['telegram_chat_id', 'telegram_message_id'], 'tg_msg_chat_msg_unique');
            $table->index(['telegram_chat_id', 'sent_at'], 'tg_msg_chat_sent_idx');
            $table->index(['telegram_chat_id', 'telegram_user_id', 'sent_at'], 'tg_msg_chat_user_sent_idx');

            if (in_array(DB::getDriverName(), ['mysql', 'pgsql', 'mariadb'], true)) {
                $table->fullText('text');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
    }
};
