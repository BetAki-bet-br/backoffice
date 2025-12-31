<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();

            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->integer('depth')->default(0);

            $table->string('title');
            $table->string('icon')->nullable();

            // Navegação
            $table->boolean('is_external')->default(false);
            $table->string('url')->nullable();
            $table->string('route_name')->nullable();
            $table->jsonb('route_params')->nullable();
            $table->string('target')->nullable();

            $table->enum('status', ['active','inactive'])->default('active');
            $table->integer('position')->default(0);

            // Controle de visibilidade por roles e/ou permissões
            $table->jsonb('visible_roles')->nullable();
            $table->jsonb('visible_permissions')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['menu_id','parent_id','position']);
            $table->index(['status','depth']);
            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};