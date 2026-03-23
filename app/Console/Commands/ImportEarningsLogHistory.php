<?php

namespace App\Console\Commands;

use App\Models\EarningsReportLog;
use Illuminate\Console\Command;

class ImportEarningsLogHistory extends Command
{
    protected $signature = 'app:import-earnings-log-history
        {file : Caminho do arquivo de log de earnings}
        {--dry-run : Apenas exibir os registros sem inserir}';

    protected $description = 'Importa histórico de envios de earnings a partir do arquivo de log de produção';

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("Arquivo não encontrado: {$filePath}");

            return self::FAILURE;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->info('Linhas lidas: '.count($lines));

        // Track request context (year per request ID)
        $requestYears = [];
        // Track failed player IDs with error messages
        $failures = [];
        // Collect records to insert
        $records = [];

        foreach ($lines as $line) {
            $timestamp = $this->extractTimestamp($line);

            // Extract year from "Iniciando envio" lines
            if (str_contains($line, 'Iniciando envio de relatórios')) {
                $reqId = $this->extractRequestId($line);
                if (preg_match('/Ano:\s*(\d{4})/', $line, $m) && $reqId) {
                    $requestYears[$reqId] = (int) $m[1];
                }

                continue;
            }

            // Track failures by Player ID
            if (str_contains($line, 'FALHA DEFINITIVA')) {
                $playerId = $this->extractField($line, 'Player ID');
                $error = $this->extractAfter($line, 'Exceção: ');
                if ($playerId) {
                    $failures[$playerId] = mb_substr($error ?: 'Unknown error', 0, 500);
                }

                continue;
            }

            // Parse "DESPACHADO" lines — successful dispatches
            if (str_contains($line, 'Job de envio DESPACHADO')) {
                $reqId = $this->extractRequestId($line);
                $playerId = $this->extractField($line, 'Player ID');
                $username = $this->extractField($line, 'Username');
                $cpf = $this->extractField($line, 'CPF');
                $email = $this->extractField($line, 'Email');
                $year = $requestYears[$reqId] ?? 2025;

                if (! $playerId || ! $email) {
                    continue;
                }

                $records[] = [
                    'player_id' => $playerId,
                    'cpf' => $cpf ?: '',
                    'email' => $email,
                    'username' => $username ?: '',
                    'year' => $year,
                    'status' => 'sent',
                    'zeroed' => false,
                    'error_message' => null,
                    'sent_at' => $timestamp,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                continue;
            }

            // Parse "NÃO ENCONTRADO" lines
            if (str_contains($line, 'NÃO ENCONTRADO na planilha')) {
                $reqId = $this->extractRequestId($line);
                $playerId = $this->extractField($line, 'Player ID');
                $cpf = $this->extractField($line, 'CPF');
                $email = $this->extractField($line, 'Email');
                $year = $requestYears[$reqId] ?? 2025;

                if (! $playerId || ! $email) {
                    continue;
                }

                $records[] = [
                    'player_id' => $playerId,
                    'cpf' => $cpf ?: '',
                    'email' => $email,
                    'username' => $playerId,
                    'year' => $year,
                    'status' => 'not_found',
                    'zeroed' => true,
                    'error_message' => null,
                    'sent_at' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                continue;
            }
        }

        // Mark records as failed if there's a corresponding failure log
        foreach ($records as &$record) {
            if ($record['status'] === 'sent' && isset($failures[$record['player_id']])) {
                // Check if this specific dispatch failed (by timestamp proximity)
                // For simplicity, only mark the earliest dispatch of a failed player
                $record['status'] = 'failed';
                $record['error_message'] = $failures[$record['player_id']];
                $record['sent_at'] = null;
                unset($failures[$record['player_id']]);
            }
        }
        unset($record);

        $sent = count(array_filter($records, fn ($r) => $r['status'] === 'sent'));
        $notFound = count(array_filter($records, fn ($r) => $r['status'] === 'not_found'));
        $failed = count(array_filter($records, fn ($r) => $r['status'] === 'failed'));

        $this->info("Registros encontrados: {$sent} enviados, {$notFound} não encontrados, {$failed} falhas");

        if ($this->option('dry-run')) {
            $this->table(
                ['Data', 'Player ID', 'CPF', 'Email', 'Ano', 'Status', 'Zerado'],
                array_map(fn ($r) => [
                    $r['created_at'],
                    $r['player_id'],
                    $r['cpf'],
                    $r['email'],
                    $r['year'],
                    $r['status'],
                    $r['zeroed'] ? 'Sim' : 'Não',
                ], $records)
            );

            $this->warn('Dry-run: nenhum registro inserido.');

            return self::SUCCESS;
        }

        $inserted = 0;
        foreach ($records as $record) {
            EarningsReportLog::create($record);
            $inserted++;
        }

        $this->info("Inseridos {$inserted} registros na tabela earnings_report_logs.");

        return self::SUCCESS;
    }

    private function extractTimestamp(string $line): ?string
    {
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $m)) {
            return $m[1];
        }

        return null;
    }

    private function extractRequestId(string $line): ?string
    {
        if (preg_match('/\[req_([a-f0-9]+)\]/', $line, $m)) {
            return 'req_'.$m[1];
        }

        return null;
    }

    private function extractField(string $line, string $field): ?string
    {
        $pattern = '/'.$field.':\s*([^,\n]+)/';
        if (preg_match($pattern, $line, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function extractAfter(string $line, string $marker): ?string
    {
        $pos = strpos($line, $marker);
        if ($pos === false) {
            return null;
        }

        $rest = substr($line, $pos + strlen($marker));
        // Truncate at ", Trace:" if present
        $tracePos = strpos($rest, ', Trace:');
        if ($tracePos !== false) {
            $rest = substr($rest, 0, $tracePos);
        }

        return trim($rest);
    }
}
