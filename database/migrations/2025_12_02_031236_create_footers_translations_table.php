<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('footer_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('footer_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('locale', 5);
            $table->string('legal_title')->nullable();
            $table->text('legal_text')->nullable();
            $table->text('disclaimer')->nullable();

            $table->unique(['footer_id', 'locale']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('footer_translations');
    }
};