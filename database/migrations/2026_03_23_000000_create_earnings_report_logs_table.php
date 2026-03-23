<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('earnings_report_logs', function (Blueprint $table) {
            $table->id();
            $table->string('player_id');
            $table->string('cpf');
            $table->string('email');
            $table->string('username')->default('');
            $table->integer('year');
            $table->string('status')->default('queued'); // queued, sent, failed, sent_zeroed
            $table->boolean('zeroed')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['year', 'status']);
            $table->index('player_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('earnings_report_logs');
    }
};
