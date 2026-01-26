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
        Schema::create('bot_users', function (Blueprint $table) {
            $table->id();

            // Relacionamento
            $table->foreignId('bot_id')->constrained('telegram_bots')->onDelete('cascade');

            // Dados do usuário Telegram
            $table->unsignedBigInteger('telegram_user_id');
            $table->string('first_name')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable();

            // Status do fluxo
            $table->enum('status', ['new', 'validating', 'validated', 'failed'])->default('new');

            // Datas
            $table->dateTime('first_interaction_at');
            $table->dateTime('validated_at')->nullable();

            // Dados adicionais
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Índices
            $table->unique(['bot_id', 'telegram_user_id']);
            $table->index(['bot_id', 'status']);
            $table->index(['bot_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_users');
    }
};
