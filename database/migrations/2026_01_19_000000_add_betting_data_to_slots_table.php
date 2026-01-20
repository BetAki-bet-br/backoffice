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
        Schema::table('slots', function (Blueprint $table) {
            $table->decimal('rtp', 5, 2)->nullable()->after('provider_game_id')->comment('Return to Player percentage');
            $table->enum('volatility', ['low', 'medium', 'high'])->nullable()->after('rtp')->comment('Game volatility');
            $table->decimal('min_bet', 12, 2)->nullable()->after('volatility')->comment('Minimum bet allowed');
            $table->decimal('max_bet', 12, 2)->nullable()->after('min_bet')->comment('Maximum bet allowed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->dropColumn(['rtp', 'volatility', 'min_bet', 'max_bet']);
        });
    }
};
