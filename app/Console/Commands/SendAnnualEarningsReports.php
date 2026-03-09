<?php

namespace App\Console\Commands;

use App\Jobs\SendPlayerEarningsEmailJob;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SendAnnualEarningsReports extends Command
{
    protected $signature = 'app:send-annual-earnings-reports
        {file : Path to the .xlsx earnings report file}
        {player-info : Path to the .xlsx player info file (with emails)}
        {--year=2025 : The report year}
        {--dry-run : Parse and validate without dispatching emails}
        {--skip-metadata=17 : Number of metadata rows to skip in the earnings file}
        {--skip-player-metadata=19 : Number of metadata rows to skip in the player info file}
        {--limit=0 : Limit the number of emails dispatched (0 = no limit)}';

    protected $description = 'Parse an annual earnings report spreadsheet and dispatch individual email reports to each player';

    private const COLUMN_MAP = [
        0 => 'currency',
        1 => 'player_id',
        2 => 'bets',
        3 => 'username',
        4 => 'bet_count',
        5 => 'wins',
        6 => 'redeemed_bonuses',
        7 => 'net_income',
        8 => 'deposits',
        9 => 'withdrawals',
        10 => 'balance_start_real',
        11 => 'balance_end_real',
        12 => 'balance_start_bonus',
        13 => 'balance_end_bonus',
    ];

    private const NUMERIC_FIELDS = [
        'bets', 'bet_count', 'wins', 'redeemed_bonuses', 'net_income',
        'deposits', 'withdrawals', 'balance_start_real', 'balance_end_real',
        'balance_start_bonus', 'balance_end_bonus',
    ];

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $playerInfoPath = $this->argument('player-info');
        $year = (int) $this->option('year');
        $dryRun = $this->option('dry-run');
        $skipMetadata = (int) $this->option('skip-metadata');
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

        // Load player info (emails) first
        $this->info("Loading player info: {$playerInfoPath}");
        $emailMap = $this->loadPlayerEmails($playerInfoPath, $skipPlayerMetadata);
        $this->info('Loaded ' . count($emailMap) . ' player emails.');

        // Load earnings data
        $this->info("Loading earnings: {$filePath}");

        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, false, false);

        $totalRows = count($rows);
        $this->info("Total rows in earnings spreadsheet: {$totalRows}");

        $dataStartIndex = $skipMetadata + 1;

        if ($dataStartIndex >= $totalRows) {
            $this->error("No data rows found after skipping {$skipMetadata} metadata rows + header.");

            return self::FAILURE;
        }

        if ($skipMetadata < $totalRows) {
            $headerRow = $rows[$skipMetadata];
            $this->info('Column headers: ' . implode(' | ', array_filter($headerRow)));
        }

        $dispatched = 0;
        $skipped = 0;
        $noEmail = 0;
        $parsed = 0;

        if ($dryRun) {
            $this->warn('DRY RUN: No emails will be dispatched.');
        }

        for ($i = $dataStartIndex; $i < $totalRows; $i++) {
            $row = $rows[$i];

            $playerId = $row[1] ?? null;
            if (empty($playerId) || ! is_numeric($playerId)) {
                $skipped++;

                continue;
            }

            $playerData = $this->parseRow($row);
            $parsed++;

            // Look up email by Player ID
            $email = $emailMap[$playerData['player_id']] ?? null;

            if (empty($email)) {
                $noEmail++;
                $this->warn("  No email found for Player {$playerData['player_id']} ({$playerData['username']}), skipping.");

                continue;
            }

            $playerData['email'] = $email;

            if ($dryRun) {
                if ($parsed <= 5) {
                    $this->line("  Sample: Player {$playerData['player_id']} ({$playerData['username']}) → {$email} — Bets: {$playerData['bets']}, Net: {$playerData['net_income']}");
                }
            } else {
                SendPlayerEarningsEmailJob::dispatch($playerData, $year);
                $dispatched++;

                if ($dispatched % 100 === 0) {
                    $this->info("Dispatched {$dispatched} emails...");
                }

                if ($limit > 0 && $dispatched >= $limit) {
                    $this->warn("Limit of {$limit} emails reached, stopping.");

                    break;
                }
            }
        }

        $this->newLine();
        $this->info("Parsed: {$parsed} players");
        $this->info("Skipped (invalid rows): {$skipped}");
        $this->info("No email found: {$noEmail}");

        if ($dryRun) {
            $this->warn('Dry run complete. No emails dispatched. ' . ($parsed - $noEmail) . ' players would receive reports.');
        } else {
            $this->info("Dispatched: {$dispatched} email jobs to the 'emails' queue.");
            $this->info("Run 'php artisan queue:work --queue=emails' or use Horizon to process them.");
        }

        return self::SUCCESS;
    }

    /**
     * Load player emails from the player info spreadsheet into a map keyed by Player ID.
     *
     * Expected columns: Player ID (0), Player username (1), Player status (2),
     * First name (3), Last name (4), Email (5)
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

    private function parseRow(array $row): array
    {
        $data = [];

        foreach (self::COLUMN_MAP as $index => $field) {
            $value = $row[$index] ?? null;

            if (in_array($field, self::NUMERIC_FIELDS)) {
                $data[$field] = $this->parseBrNumber($value);
            } else {
                $data[$field] = trim((string) $value);
            }
        }

        return $data;
    }

    /**
     * Parse a Brazilian-formatted number.
     *
     * PhpSpreadsheet may return the value as a float directly if the cell is numeric,
     * or as a string with Brazilian formatting (period for thousands, comma for decimals).
     */
    private function parseBrNumber(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);

        if ($value === '' || $value === '-') {
            return 0.0;
        }

        // Brazilian format: 1.086 (thousands) and 183,70 (decimals)
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return (float) $value;
    }
}
