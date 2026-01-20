<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add cover_url to categories table
        Schema::table('categories', function (Blueprint $table) {
            $table->string('cover_url')->nullable()->after('slug');
        });

        // Add cover_url to banners table
        Schema::table('banners', function (Blueprint $table) {
            $table->string('cover_url')->nullable()->after('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('cover_url');
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('cover_url');
        });
    }
};
