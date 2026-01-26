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
        Schema::create('bot_messages', function (Blueprint $table) {
            $table->id();

            // Relacionamentos
            $table->foreignId('bot_id')->constrained('telegram_bots')->onDelete('cascade');
            $table->foreignId('bot_user_id')->nullable()->constrained('bot_users')->onDelete('set null');

            // IDs externos
            $table->unsignedBigInteger('telegram_message_id')->nullable();

            // Tipo de mensagem
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->string('type'); // text, button_callback, etc

            // Conteúdo
            $table->text('content');

            // Status
            $table->enum('status', ['sent', 'delivered', 'failed'])->default('sent');

            // Metadados adicionais
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['bot_id', 'created_at']);
            $table->index(['bot_user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_messages');
    }
};
