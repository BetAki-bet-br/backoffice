<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        // slots: title, provider
        DB::statement('CREATE INDEX IF NOT EXISTS idx_slots_title_trgm ON slots USING gin (title gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_slots_provider_trgm ON slots USING gin (provider gin_trgm_ops)');

        // categories: name, slug
        DB::statement('CREATE INDEX IF NOT EXISTS idx_categories_name_trgm ON categories USING gin (name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_categories_slug_trgm ON categories USING gin (slug gin_trgm_ops)');

        // menus: name, slug
        DB::statement('CREATE INDEX IF NOT EXISTS idx_menus_name_trgm ON menus USING gin (name gin_trgm_ops)');

        // showcases: title, slug
        DB::statement('CREATE INDEX IF NOT EXISTS idx_showcases_title_trgm ON showcases USING gin (title gin_trgm_ops)');

        // top_lists: title
        DB::statement('CREATE INDEX IF NOT EXISTS idx_top_lists_title_trgm ON top_lists USING gin (title gin_trgm_ops)');

        // awarded_game_batches: title
        DB::statement('CREATE INDEX IF NOT EXISTS idx_awarded_game_batches_title_trgm ON awarded_game_batches USING gin (title gin_trgm_ops)');

        // top_winner_batches: title
        DB::statement('CREATE INDEX IF NOT EXISTS idx_top_winner_batches_title_trgm ON top_winner_batches USING gin (title gin_trgm_ops)');

        // users: name, email
        DB::statement('CREATE INDEX IF NOT EXISTS idx_users_name_trgm ON users USING gin (name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_users_email_trgm ON users USING gin (email gin_trgm_ops)');

        // portal_games: name
        DB::statement('CREATE INDEX IF NOT EXISTS idx_portal_games_name_trgm ON portal_games USING gin (name gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_slots_title_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_slots_provider_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_categories_name_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_categories_slug_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_menus_name_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_showcases_title_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_top_lists_title_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_awarded_game_batches_title_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_top_winner_batches_title_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_users_name_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_users_email_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_portal_games_name_trgm');
    }
};
