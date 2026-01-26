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
        Schema::create('telegram_bots', function (Blueprint $table) {
            $table->id();

            // Informações básicas
            $table->string('name');
            $table->string('bot_token')->unique();
            $table->string('username')->unique();
            $table->text('description')->nullable();

            // Configurações de API
            $table->string('api_url');
            $table->string('api_key');
            $table->unsignedInteger('portal_id');
            $table->string('group_chat_id')->nullable();
            $table->string('group_invite_link')->nullable();
            $table->string('register_url')->nullable();
            $table->string('webhook_secret')->unique();

            // Status e controle
            $table->enum('status', ['active', 'inactive', 'paused'])->default('active');
            $table->boolean('debug_mode')->default(false);
            $table->dateTime('webhook_set_at')->nullable();
            $table->dateTime('webhook_tested_at')->nullable();

            // Auditoria
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Índices para performance
            $table->index('status');
            $table->index('created_by');
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
