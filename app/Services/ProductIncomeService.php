<?php

namespace App\Services;

use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class ProductIncomeService
{
    private const HEADER_MARKER = 'Product type';

    private const COLUMN_MAP = [
        0 => 'product_type',
        1 => 'player_id',
        2 => 'username',
        3 => 'income',
        4 => 'balance_end',
    ];

    private const NUMERIC_FIELDS = ['income', 'balance_end'];

    /**
     * Find all product-type entries for a single player ID.
     *
     * @return array<int, array> List of entries (one per product type)
     */
    public function findByPlayerId(string $filePath, string $playerId): array
    {
        $reader = new XlsxReader;
        $reader->open($filePath);

        $headerFound = false;
        $results = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->toArray();

                if (! $headerFound) {
                    $firstCell = trim((string) ($cells[0] ?? ''));
                    if ($firstCell === self::HEADER_MARKER) {
                        $headerFound = true;
                    }

                    continue;
                }

                $cellPlayerId = $cells[1] ?? null;
                if (empty($cellPlayerId) || ! is_numeric($cellPlayerId)) {
                    continue;
                }

                if ((string) $cellPlayerId === $playerId) {
                    $results[] = $this->parseRow($cells);
                }
            }

            break; // Only process first sheet
        }

        $reader->close();

        return $results;
    }

    /**
     * Find product-type entries for multiple player IDs in a single pass.
     *
     * @param  array<string>  $playerIds
     * @return array<string, array<int, array>> Keyed by player_id, each containing a list of entries
     */
    public function findByPlayerIds(string $filePath, array $playerIds): array
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
                    if ($firstCell === self::HEADER_MARKER) {
                        $headerFound = true;
                    }

                    continue;
                }

                $cellPlayerId = $cells[1] ?? null;
                if (empty($cellPlayerId) || ! is_numeric($cellPlayerId)) {
                    continue;
                }

                $id = (string) $cellPlayerId;
                if (isset($lookup[$id])) {
                    $results[$id][] = $this->parseRow($cells);
                }
            }

            break; // Only process first sheet
        }

        $reader->close();

        return $results;
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

        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return (float) $value;
    }

    /**
     * Get the default storage path for the income by product file.
     */
    public static function storagePath(): string
    {
        return base_path('income_by_product.xlsx');
    }
}
