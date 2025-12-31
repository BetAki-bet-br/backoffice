<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('slots', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('cover_url')->nullable();
            $table->enum('status', ['active','inactive'])->default('inactive');

            // Mapeamento do provedor
            $table->string('provider')->index();
            $table->string('provider_game_id')->index();

            // Metadados
            $table->jsonb('tags')->nullable();
            $table->integer('position')->default(0);

            // Auditoria mínima
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['provider','provider_game_id']);
            $table->index(['status','position']);
            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slots');
    }
};