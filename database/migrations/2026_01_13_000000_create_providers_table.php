<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();

            $table->string('external_id')->unique()->comment('Provider ID from external API (productId)');
            $table->string('name')->comment('Provider Name from external API (productName)');
            
            $table->integer('game_count')->default(0);
            
            $table->enum('status', ['active', 'inactive'])->default('active');
            
            $table->timestamps();
            
            $table->index('name');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
