<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('top_winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('top_winner_batches')->cascadeOnDelete();

            $table->string('player_ref')->nullable();
            $table->string('display_name');
            $table->string('country', 2)->nullable(); // ISO-3166 alpha-2

            $table->integer('rank');
            $table->integer('position')->default(0);

            // métricas agregadas do período
            $table->bigInteger('wins_count')->default(0);
            $table->decimal('prize_sum', 18, 2)->default(0);
            $table->decimal('max_prize', 18, 2)->default(0);
            $table->decimal('avg_prize', 18, 2)->default(0);

            $table->jsonb('meta')->nullable();

            $table->timestampsTz();

            $table->index(['batch_id', 'rank']);
            $table->index(['batch_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('top_winners');
    }
};
