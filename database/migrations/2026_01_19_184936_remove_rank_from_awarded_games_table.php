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
        Schema::table('awarded_games', function (Blueprint $table) {
            // Drop index first before dropping column
            if (Schema::hasTable('awarded_games')) {
                $table->dropIndex('awarded_games_batch_id_rank_index');
            }
            $table->dropColumn('rank');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('awarded_games', function (Blueprint $table) {
            $table->integer('rank')->default(1)->after('slot_id');
        });
    }
};
