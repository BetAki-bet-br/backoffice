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
        Schema::create('bot_statistics', function (Blueprint $table) {
            $table->id();

            // Relacionamento
            $table->foreignId('bot_id')->constrained('telegram_bots')->onDelete('cascade');

            // Data
            $table->date('date');

            // Contadores
            $table->unsignedInteger('messages_sent')->default(0);
            $table->unsignedInteger('messages_received')->default(0);
            $table->unsignedInteger('users_new')->default(0);
            $table->unsignedInteger('validations_success')->default(0);
            $table->unsignedInteger('validations_failed')->default(0);

            $table->timestamps();

            // Índices e constraint único
            $table->unique(['bot_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_statistics');
    }
};
