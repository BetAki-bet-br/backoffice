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
        Schema::create('bot_flows', function (Blueprint $table) {
            $table->id();

            // Relacionamento
            $table->foreignId('bot_id')->constrained('telegram_bots')->onDelete('cascade');

            // Informações básicas
            $table->string('name');
            $table->text('description')->nullable();

            // Versionamento
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('based_on_flow_id')->nullable()->constrained('bot_flows')->nullOnDelete();

            // Conteúdo
            $table->json('flow_data');

            // Status e controle
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->boolean('is_default')->default(false);

            // Auditoria
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['bot_id', 'status']);
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_flows');
    }
};
