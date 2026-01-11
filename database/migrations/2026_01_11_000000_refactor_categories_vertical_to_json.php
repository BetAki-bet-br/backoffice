<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add 'verticals' json column
        Schema::table('categories', function (Blueprint $table) {
            $table->json('verticals')->nullable()->after('slug');
        });

        // 2. Migrate data and Merge duplicates based on slug
        $categories = DB::table('categories')->get();
        
        // Group by slug to identify duplicates to merge
        $grouped = $categories->groupBy('slug');

        foreach ($grouped as $slug => $rows) {
            // Take the first one as the master
            $master = $rows->first();
            
            // Collect all verticals from the group
            $allVerticals = $rows->pluck('vertical')->unique()->values()->toArray();
            
            // Update master with all verticals
            DB::table('categories')
                ->where('id', $master->id)
                ->update(['verticals' => json_encode($allVerticals)]);

            // If there are duplicates, merge relations and delete them
            if ($rows->count() > 1) {
                $duplicateIds = $rows->where('id', '!=', $master->id)->pluck('id')->toArray();
                
                // Re-point category_slot pivot entries to master
                DB::table('category_slot')
                    ->whereIn('category_id', $duplicateIds)
                    ->update(['category_id' => $master->id]);

                // Delete duplicates
                DB::table('categories')->whereIn('id', $duplicateIds)->delete();
            }
        }

        // 3. Drop 'vertical' column
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('vertical');
            // 'slug' is already unique from the creation migration, so we don't need to add it again.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->enum('vertical', ['slots', 'live'])->default('slots')->after('slug')->index();
        });

        // Restore 'vertical' from first element of 'verticals'
        $categories = DB::table('categories')->get();
        foreach ($categories as $cat) {
            $verticals = json_decode($cat->verticals, true);
            $primaryVertical = is_array($verticals) && count($verticals) > 0 ? $verticals[0] : 'slots';
            
            DB::table('categories')
                ->where('id', $cat->id)
                ->update(['vertical' => $primaryVertical]);
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('verticals');
        });
    }
};
