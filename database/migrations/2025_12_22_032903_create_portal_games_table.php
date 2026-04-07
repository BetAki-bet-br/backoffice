<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_games', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('portal_id')->index();

            // externalId do swagger (string). É o que casa com game_extras.external_id (CSV)
            $table->string('external_id', 80)->index();

            // alguns campos úteis da base
            $table->string('name', 255)->nullable();
            $table->string('product_name', 255)->nullable();
            $table->string('supplier_name', 255)->nullable();

            // guarda o payload bruto para debug/auditoria
            $table->jsonb('payload')->nullable();

            $table->timestamps();

            $table->unique(['portal_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_games');
    }
};
