<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('top_list_slot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('top_list_id')->constrained('top_lists')->cascadeOnDelete();
            $table->foreignId('slot_id')->constrained('slots')->cascadeOnDelete();
            $table->integer('position')->default(0);
            $table->timestampsTz();

            $table->unique(['top_list_id','slot_id']);
            $table->index(['top_list_id','position']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('top_list_slot');
    }
};