<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('awarded_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('awarded_game_batches')->cascadeOnDelete();

            $table->foreignId('slot_id')->constrained('slots')->cascadeOnDelete();
            $table->integer('rank');
            $table->integer('position')->default(0);

            // métricas agregadas (exemplos usuais)
            $table->bigInteger('wins_count')->default(0);
            $table->decimal('prize_sum', 18, 2)->default(0);
            $table->decimal('max_prize', 18, 2)->default(0);
            $table->decimal('avg_prize', 18, 2)->default(0);

            // extras
            $table->jsonb('meta')->nullable();

            $table->timestampsTz();

            $table->unique(['batch_id','slot_id']);
            $table->index(['batch_id','rank']);
            $table->index(['batch_id','position']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('awarded_games');
    }
};