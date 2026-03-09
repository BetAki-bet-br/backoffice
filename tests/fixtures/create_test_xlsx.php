<?php

/**
 * Helper script to generate the test fixture .xlsx files.
 * Run: php tests/fixtures/create_test_xlsx.php
 */
require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// --- Earnings file ---

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$metadata = [
    ['Data source: Player detail'],
    ['Brand: All'],
    ['Player currency: All'],
    ['Vip level: All'],
    ['Country: All'],
    ['Language: All'],
    ['Player custom field: All'],
    ['Registration portal: All'],
    ['Marketing channel: All'],
    ['Marketing source: All'],
    ['Player status: All'],
    ['Internal players: false'],
    ['Filter players by date type: false'],
    ['Date type: Last login date'],
    ['Additional time period:  -'],
    ['Time period: 1/1/2025, 12:00:00 AM - 1/1/2026, 12:00:00 AM'],
    ['Btags:'],
];

$rowIndex = 1;
foreach ($metadata as $meta) {
    $sheet->setCellValue("A{$rowIndex}", $meta[0]);
    $rowIndex++;
}

$headers = [
    'Player currency', 'Player ID', 'Bets', 'Player username', 'Bet count',
    'Wins', 'Redeemed bonuses', 'Net income', 'Deposits', 'Withdrawals',
    'Real money balance start', 'Real money balance end', 'Bonus balance start', 'Bonus balance end',
];

$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue("{$col}{$rowIndex}", $header);
    $col++;
}
$rowIndex++;

$data = [
    ['BRL', 10310001, 183.70, '00000427063', 271, 83.67, 0.00, 100.03, 100.00, 0.00, 0.00, 0.33, 0.00, 0.00],
    ['BRL', 10310007, 32.04, '00007235330', 231, 44.10, 0.00, -12.06, 10.00, 22.00, 0.00, 0.06, 0.00, 0.00],
    ['BRL', 10310008, 0.00, '00007734255', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.12, 0.00, 0.00],
];

foreach ($data as $row) {
    $col = 'A';
    foreach ($row as $value) {
        $sheet->setCellValue("{$col}{$rowIndex}", $value);
        $col++;
    }
    $rowIndex++;
}

$writer = new Xlsx($spreadsheet);
$writer->save(__DIR__ . '/earnings_sample.xlsx');
echo "Created tests/fixtures/earnings_sample.xlsx\n";

// --- Player info file ---

$spreadsheet2 = new Spreadsheet();
$sheet2 = $spreadsheet2->getActiveSheet();

$playerMetadata = [
    ['Data source: Player info'],
    ['Player custom field: All'],
    ['Date type: Registration date'],
    ['Brand: All'],
    ['Player currency: All'],
    ['Vip level: All'],
    ['Country: All'],
    ['Language: All'],
    ['Registration portal: All'],
    ['Marketing channel: All'],
    ['Marketing source: All'],
    ['Player status: All'],
    ['Internal players: false'],
    ['Blocked point awarding: false'],
    ['Btags:'],
    ['Player custom field values:'],
    ['Time period: 1/1/1901, 12:00:00 AM - 3/10/2026, 12:00:00 AM'],
    ['Convert currency: true'],
    ['Player usernames:'],
];

$rowIndex = 1;
foreach ($playerMetadata as $meta) {
    $sheet2->setCellValue("A{$rowIndex}", $meta[0]);
    $rowIndex++;
}

$playerHeaders = ['Player ID', 'Player username', 'Player status', 'First name', 'Last name', 'Email'];
$col = 'A';
foreach ($playerHeaders as $header) {
    $sheet2->setCellValue("{$col}{$rowIndex}", $header);
    $col++;
}
$rowIndex++;

$playerData = [
    [10310001, '00000427063', 'Active', 'João', 'Silva', 'joao@example.com'],
    [10310007, '00007235330', 'Active', 'Maria', 'Santos', 'maria@example.com'],
    // Player 10310008 intentionally omitted to test missing email scenario
];

foreach ($playerData as $row) {
    $col = 'A';
    foreach ($row as $value) {
        $sheet2->setCellValue("{$col}{$rowIndex}", $value);
        $col++;
    }
    $rowIndex++;
}

$writer2 = new Xlsx($spreadsheet2);
$writer2->save(__DIR__ . '/player_info_sample.xlsx');
echo "Created tests/fixtures/player_info_sample.xlsx\n";
