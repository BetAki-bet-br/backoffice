<?php

namespace App\Console\Commands;

use App\Jobs\SendPlayerEarningsEmailJob;
use App\Services\EarningsReportService;
use App\Services\ProductIncomeService;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SendAnnualEarningsReports extends Command
{
    protected $signature = 'app:send-annual-earnings-reports
        {file : Path to the .xlsx earnings report file}
        {player-info : Path to the .xlsx player info file (with emails)}
        {--year=2025 : The report year}
        {--dry-run : Parse and validate without dispatching emails}
        {--skip-player-metadata=19 : Number of metadata rows to skip in the player info file}
        {--limit=0 : Limit the number of emails dispatched (0 = no limit)}
        {--income-file= : Path to the income by product type .xlsx file}';

    protected $description = 'Parse an annual earnings report spreadsheet and dispatch individual email reports to each player';

    public function handle(EarningsReportService $service, ProductIncomeService $productIncomeService): int
    {
        $filePath = $this->argument('file');
        $playerInfoPath = $this->argument('player-info');
        $incomeFilePath = $this->option('income-file');
        $year = (int) $this->option('year');
        $dryRun = $this->option('dry-run');
        $skipPlayerMetadata = (int) $this->option('skip-player-metadata');
        $limit = (int) $this->option('limit');

        if (! file_exists($filePath)) {
            $this->error("Earnings file not found: {$filePath}");

            return self::FAILURE;
        }

        if (! file_exists($playerInfoPath)) {
            $this->error("Player info file not found: {$playerInfoPath}");

            return self::FAILURE;
        }

        if ($incomeFilePath && ! file_exists($incomeFilePath)) {
            $this->error("Income by product file not found: {$incomeFilePath}");

            return self::FAILURE;
        }

        // Load product income data if provided
        $productIncomesByPlayer = [];
        if ($incomeFilePath) {
            $this->info("Loading income by product: {$incomeFilePath}");
            // We'll collect all player IDs first then do a single pass — but since we stream,
            // we pre-load all product incomes keyed by player_id
            $allProductIncomes = [];
            $incomeReader = new \OpenSpout\Reader\XLSX\Reader;
            $incomeReader->open($incomeFilePath);
            $headerFound = false;
            foreach ($incomeReader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $cells = $row->toArray();
                    if (! $headerFound) {
                        if (trim((string) ($cells[0] ?? '')) === 'Product type') {
                            $headerFound = true;
                        }

                        continue;
                    }
                    $pid = $cells[1] ?? null;
                    if (empty($pid) || ! is_numeric($pid)) {
                        continue;
                    }
                    $allProductIncomes[(string) $pid][] = $productIncomeService->parseRow($cells);
                }

                break;
            }
            $incomeReader->close();
            $productIncomesByPlayer = $allProductIncomes;
            $this->info('Loaded product incomes for '.count($productIncomesByPlayer).' players.');
        }

        // Load player info (emails) — small file, PhpSpreadsheet is fine
        $this->info("Loading player info: {$playerInfoPath}");
        $emailMap = $this->loadPlayerEmails($playerInfoPath, $skipPlayerMetadata);
        $this->info('Loaded '.count($emailMap).' player emails.');

        // Track which player-info entries get matched
        $matchedPlayerIds = [];

        // Stream earnings data row-by-row using the service
        $this->info("Streaming earnings: {$filePath}");

        $dispatched = 0;
        $noEmail = 0;

        if ($dryRun) {
            $this->warn('DRY RUN: No emails will be dispatched.');
        }

        $stats = $service->streamAllPlayers($filePath, function (array $playerData) use (
            $emailMap, $dryRun, $year, $limit, $productIncomesByPlayer, &$dispatched, &$noEmail, &$matchedPlayerIds
        ) {
            $playerIdStr = $playerData['player_id'];
            $email = $emailMap[$playerIdStr] ?? null;

            if (empty($email)) {
                $noEmail++;

                return;
            }

            $matchedPlayerIds[] = $playerIdStr;
            $playerData['email'] = $email;
            $playerData['product_incomes'] = $productIncomesByPlayer[$playerIdStr] ?? [];

            if ($dryRun) {
                $productTypes = ! empty($playerData['product_incomes'])
                    ? implode(', ', array_column($playerData['product_incomes'], 'product_type'))
                    : 'none';
                $this->line("  Match: Player {$playerData['player_id']} ({$playerData['username']}) → {$email} — Bets: {$playerData['bets']}, Net: {$playerData['net_income']}, Products: {$productTypes}");
            } else {
                SendPlayerEarningsEmailJob::dispatch($playerData, $year);
                $dispatched++;

                if ($dispatched % 100 === 0) {
                    $this->info("Dispatched {$dispatched} emails...");
                }

                if ($limit > 0 && $dispatched >= $limit) {
                    $this->warn("Limit of {$limit} emails reached, stopping.");

                    return false;
                }
            }
        });

        if (! $stats['header_found']) {
            $this->error("Could not find column header row (looking for 'Player currency' in first column).");

            return self::FAILURE;
        }

        if ($stats['headers']) {
            $this->info('Column headers: '.$stats['headers']);
        }

        // Report unmatched player-info entries
        $unmatchedPlayers = array_diff_key($emailMap, array_flip($matchedPlayerIds));

        $this->newLine();
        $this->info("Parsed: {$stats['parsed']} players from earnings");
        $this->info("Skipped (invalid rows): {$stats['skipped']}");
        $this->info("No email in player-info: {$noEmail}");
        $this->info('Matched: '.count($matchedPlayerIds).' / '.count($emailMap).' player-info entries');

        if (! empty($unmatchedPlayers)) {
            $this->newLine();
            $this->warn('Players from player-info NOT found in earnings ('.count($unmatchedPlayers).'):');
            $this->table(
                ['Player ID', 'Email'],
                array_map(fn ($email, $id) => [$id, $email], $unmatchedPlayers, array_keys($unmatchedPlayers)),
            );
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('Dry run complete. No emails dispatched. '.count($matchedPlayerIds).' players would receive reports.');
        } else {
            $this->newLine();
            $this->info("Dispatched: {$dispatched} email jobs to the 'emails' queue.");
            $this->info("Run 'php artisan queue:work --queue=emails' or use Horizon to process them.");
        }

        return self::SUCCESS;
    }

    /**
     * Load player emails from the player info spreadsheet into a map keyed by Player ID.
     *
     * @return array<string, string> Player ID => email
     */
    private function loadPlayerEmails(string $filePath, int $skipMetadata): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, false, false);

        $dataStartIndex = $skipMetadata + 1;
        $emailMap = [];

        for ($i = $dataStartIndex; $i < count($rows); $i++) {
            $row = $rows[$i];
            $playerId = $row[0] ?? null;
            $email = trim((string) ($row[5] ?? ''));

            if (empty($playerId) || ! is_numeric($playerId) || empty($email)) {
                continue;
            }

            $emailMap[(string) $playerId] = $email;
        }

        return $emailMap;
    }
}
