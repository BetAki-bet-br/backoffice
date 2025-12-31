<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('category_slot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('slot_id')->constrained('slots')->cascadeOnDelete();
            $table->integer('position')->default(0);
            $table->timestampsTz();

            $table->unique(['category_id','slot_id']);
            $table->index(['category_id','position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_slot');
    }
};