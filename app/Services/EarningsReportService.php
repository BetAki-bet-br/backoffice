<?php

namespace App\Services;

use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class EarningsReportService
{
    private const EARNINGS_HEADER_MARKER = 'Player currency';

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

    /**
     * Find a single player by ID in the earnings file.
     *
     * Streams the xlsx looking for the given player_id in column 1.
     *
     * @return array|null Parsed player data or null if not found
     */
    public function findPlayerById(string $filePath, string $playerId): ?array
    {
        $reader = new XlsxReader;
        $reader->open($filePath);

        $headerFound = false;
        $result = null;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->toArray();

                if (! $headerFound) {
                    $firstCell = trim((string) ($cells[0] ?? ''));
                    if ($firstCell === self::EARNINGS_HEADER_MARKER) {
                        $headerFound = true;
                    }

                    continue;
                }

                $cellPlayerId = $cells[1] ?? null;
                if (empty($cellPlayerId) || ! is_numeric($cellPlayerId)) {
                    continue;
                }

                if ((string) $cellPlayerId === $playerId) {
                    $result = $this->parseRow($cells);

                    break 2;
                }
            }

            break;
        }

        $reader->close();

        return $result;
    }

    /**
     * Find multiple players by their IDs in a single pass through the earnings file.
     *
     * @param  array<string>  $playerIds
     * @return array<string, array> Keyed by player_id. Missing IDs are not included.
     */
    public function findPlayersByIds(string $filePath, array $playerIds): array
    {
        $lookup = array_flip($playerIds);
        $results = [];

        $reader = new XlsxReader;
        $reader->open($filePath);

        $headerFound = false;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->toArray();

                if (! $headerFound) {
                    $firstCell = trim((string) ($cells[0] ?? ''));
                    if ($firstCell === self::EARNINGS_HEADER_MARKER) {
                        $headerFound = true;
                    }

                    continue;
                }

                $cellPlayerId = $cells[1] ?? null;
                if (empty($cellPlayerId) || ! is_numeric($cellPlayerId)) {
                    continue;
                }

                $id = (string) $cellPlayerId;
                if (isset($lookup[$id]) && ! isset($results[$id])) {
                    $results[$id] = $this->parseRow($cells);

                    if (count($results) === count($lookup)) {
                        break 2;
                    }
                }
            }

            break;
        }

        $reader->close();

        return $results;
    }

    /**
     * Stream all players from the earnings file, calling the callback for each valid row.
     *
     * @param  callable(array $playerData): ?bool  $callback  Return false to stop iteration
     * @return array{header_found: bool, parsed: int, skipped: int, headers: string|null}
     */
    public function streamAllPlayers(string $filePath, callable $callback): array
    {
        $reader = new XlsxReader;
        $reader->open($filePath);

        $headerFound = false;
        $parsed = 0;
        $skipped = 0;
        $headers = null;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->toArray();

                if (! $headerFound) {
                    $firstCell = trim((string) ($cells[0] ?? ''));
                    if ($firstCell === self::EARNINGS_HEADER_MARKER) {
                        $headerFound = true;
                        $headers = implode(' | ', array_filter(array_map('strval', $cells)));
                    }

                    continue;
                }

                $playerId = $cells[1] ?? null;
                if (empty($playerId) || ! is_numeric($playerId)) {
                    $skipped++;

                    continue;
                }

                $playerData = $this->parseRow($cells);
                $parsed++;

                if ($callback($playerData) === false) {
                    break 2;
                }
            }

            break; // Only process first sheet
        }

        $reader->close();

        return [
            'header_found' => $headerFound,
            'parsed' => $parsed,
            'skipped' => $skipped,
            'headers' => $headers,
        ];
    }

    public function parseRow(array $row): array
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
     * OpenSpout returns cell values as-is (float for numeric cells, string for text).
     */
    public function parseBrNumber(mixed $value): float
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

    /**
     * Build a zeroed-out player data array for players not found in the spreadsheet.
     */
    public static function buildZeroedPlayerData(string $playerId): array
    {
        return [
            'currency' => 'BRL',
            'player_id' => $playerId,
            'username' => '',
            'bets' => 0.0,
            'bet_count' => 0.0,
            'wins' => 0.0,
            'redeemed_bonuses' => 0.0,
            'net_income' => 0.0,
            'deposits' => 0.0,
            'withdrawals' => 0.0,
            'balance_start_real' => 0.0,
            'balance_end_real' => 0.0,
            'balance_start_bonus' => 0.0,
            'balance_end_bonus' => 0.0,
        ];
    }

    /**
     * Get the default storage path for the earnings file.
     *
     * Uses the committed earnings.xlsx at the project root.
     */
    public static function storagePath(): string
    {
        return base_path('earnings.xlsx');
    }
}
