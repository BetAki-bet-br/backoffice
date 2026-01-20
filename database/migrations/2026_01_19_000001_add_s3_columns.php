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
        Schema::table('banners', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->after('cover_url')->comment('File path in S3');
        });

        Schema::table('slots', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->after('cover_url')->comment('File path in S3');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->comment('File path in S3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('cover_path');
        });

        Schema::table('slots', function (Blueprint $table) {
            $table->dropColumn('cover_path');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('cover_path');
        });
    }
};
