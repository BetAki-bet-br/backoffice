<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('showcase_slot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('showcase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('slot_id')->constrained('slots')->cascadeOnDelete();
            $table->integer('position')->default(0);
            $table->timestampsTz();

            $table->unique(['showcase_id','slot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showcase_slot');
    }
};