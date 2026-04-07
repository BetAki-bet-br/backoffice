<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('top_lists', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('vertical', ['slots', 'live'])->default('slots');
            $table->enum('type', ['manual', 'auto'])->default('manual');
            $table->enum('status', ['draft', 'scheduled', 'published', 'archived'])->default('draft');
            $table->integer('position')->default(0);
            $table->timestampTz('valid_from')->nullable();
            $table->timestampTz('valid_until')->nullable();

            $table->jsonb('criteria')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['status', 'position']);
            $table->index(['valid_from', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('top_lists');
    }
};
