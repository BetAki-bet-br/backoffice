<?php

namespace App\Console\Commands;

use App\Models\Domain\Casino\Category;
use Illuminate\Console\Command;

class FixCategoryTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-category-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Correção dos tipos de categorias baseado no meta data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando correção de tipos de categorias...');

        $categories = Category::all();
        $updatedCount = 0;

        foreach ($categories as $category) {
            $meta = $category->meta;

            if (empty($meta) || ! isset($meta['type'])) {
                continue;
            }

            $metaType = $meta['type'];
            $currentType = $category->type;

            // Normaliza para comparação (ex: 'game-list' vs 'game-list')
            if ($metaType !== $currentType) {
                $this->line("Corrigindo Categoria #{$category->id} ({$category->name}): '{$currentType}' -> '{$metaType}'");

                $category->type = $metaType;
                $category->save();

                $updatedCount++;
            }
        }

        $this->info("Concluído! Total de categorias corrigidas: {$updatedCount}");
    }
}
