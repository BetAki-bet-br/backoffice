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
        Schema::table('awarded_game_batches', function (Blueprint $table) {
            $table->decimal('prize_sum_initial', 16, 2)->nullable()->after('criteria');
            $table->decimal('prize_sum_final', 16, 2)->nullable()->after('prize_sum_initial');
            $table->unsignedInteger('increment_interval_minutes')->nullable()->after('prize_sum_final');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('awarded_game_batches', function (Blueprint $table) {
            $table->dropColumn(['prize_sum_initial', 'prize_sum_final', 'increment_interval_minutes']);
        });
    }
};
