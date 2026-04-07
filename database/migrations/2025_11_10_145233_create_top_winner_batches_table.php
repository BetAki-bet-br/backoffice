<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('top_winner_batches', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft');

            $table->timestampTz('period_start');
            $table->timestampTz('period_end');

            $table->jsonb('criteria')->nullable();

            $table->integer('top_n')->default(10);
            $table->enum('vertical', ['slots', 'live'])->default('slots');

            $table->timestampTz('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['status', 'vertical']);
            $table->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('top_winner_batches');
    }
};
