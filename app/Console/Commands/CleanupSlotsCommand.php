<?php

namespace App\Console\Commands;

use App\Models\Domain\Casino\Slot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupSlotsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slots:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove slots that are not present in the green_flagged_external_game_ids.json file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting slot cleanup...');

        try {
            $jsonPath = base_path('green_flagged_external_game_ids.json');
            if (! File::exists($jsonPath)) {
                $this->error('green_flagged_external_game_ids.json not found.');

                return 1;
            }

            $jsonContent = File::get($jsonPath);
            $data = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON in green_flagged_external_game_ids.json');

                return 1;
            }

            $externalGameIds = $data['external_game_ids'];
            $this->info('Found '.count($externalGameIds).' game IDs in the JSON file.');

            $slotsToDelete = Slot::whereNotIn('provider_game_id', $externalGameIds)->get();

            if ($slotsToDelete->isEmpty()) {
                $this->info('No slots to delete.');

                return 0;
            }

            $this->info('Found '.$slotsToDelete->count().' slots to delete.');

            foreach ($slotsToDelete as $slot) {
                $slot->delete();
            }

            $this->info('Slot cleanup finished successfully.');

            return 0;

        } catch (\Exception $e) {
            $this->error('An error occurred during slot cleanup: '.$e->getMessage());

            return 1;
        }
    }
}
