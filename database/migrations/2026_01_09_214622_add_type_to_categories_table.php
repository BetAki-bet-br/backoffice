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
        Schema::table('categories', function (Blueprint $table) {
            $table->string('type', 50)->default('game-list')->after('vertical')->index();
        });
        
        // Opcional: Migrar dados existentes do meta['type'] para a coluna nova
        // Se houver muitos dados, isso poderia ser feito num seeder ou comando separado.
        // Como é um ambiente dev/staging, vou deixar o default.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};