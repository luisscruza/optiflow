<?php

declare(strict_types=1);

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
        Schema::create('telegram_bots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('workspace_id');
            $table->string('name'); // Friendly name for the bot
            $table->string('bot_username')->nullable(); // @BotUsername
            $table->text('bot_token'); // Encrypted token
            $table->string('default_chat_id')->nullable(); // Default chat/group/channel ID
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('workspace_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_bots');
    }
};
