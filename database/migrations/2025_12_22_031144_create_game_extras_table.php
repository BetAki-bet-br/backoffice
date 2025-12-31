<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_extras', function (Blueprint $table) {
            $table->id();

            // externalId / Game id do CSV
            $table->string('external_id', 50)->unique();

            // RTP no CSV vem tipo 95.22
            $table->decimal('rtp', 6, 2)->nullable();

            // Volatility no CSV
            $table->string('volatility', 30)->nullable();

            // Minimum Bet pode ser decimal
            $table->decimal('min_bet', 12, 4)->nullable();

            // útil pra auditoria
            $table->string('source', 30)->default('csv');

            $table->timestamps();

            $table->index('external_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_extras');
    }
};