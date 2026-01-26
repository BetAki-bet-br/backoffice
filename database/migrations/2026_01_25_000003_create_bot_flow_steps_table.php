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
        Schema::create('bot_flow_steps', function (Blueprint $table) {
            $table->id();

            // Relacionamento
            $table->foreignId('flow_id')->constrained('bot_flows')->onDelete('cascade');

            // Tipo de step
            $table->enum('type', [
                'message',
                'buttons',
                'input',
                'validation',
                'condition',
                'action'
            ]);

            // Ordem no fluxo
            $table->unsignedInteger('order');

            // Dados específicos do tipo (JSON)
            $table->json('data');

            // Metadados
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['flow_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_flow_steps');
    }
};
