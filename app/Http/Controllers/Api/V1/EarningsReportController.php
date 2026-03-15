<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SendPlayerEarningsEmailJob;
use App\Services\EarningsReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class EarningsReportController extends Controller
{
    public function __construct(
        private readonly EarningsReportService $service,
    ) {}

    /**
     * Check if the committed earnings file is present.
     */
    public function status(): JsonResponse
    {
        $path = EarningsReportService::storagePath();

        if (! file_exists($path)) {
            return response()->json(['uploaded' => false]);
        }

        return response()->json([
            'uploaded' => true,
            'file' => basename($path),
            'size' => filesize($path),
            'uploaded_at' => date('c', filemtime($path)),
        ]);
    }

    /**
     * Send earnings reports to the specified players.
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2099'],
            'players' => ['required', 'array', 'min:1'],
            'players.*.cpf' => ['required', 'string'],
            'players.*.player_id' => ['required', 'string'],
            'players.*.email' => ['required', 'email'],
        ]);

        $filePath = EarningsReportService::storagePath();

        if (! file_exists($filePath)) {
            Log::channel('earnings')->error('[EARNINGS] Arquivo earnings.xlsx não encontrado em: ' . $filePath);

            return response()->json([
                'error' => ['message' => 'Arquivo earnings.xlsx não encontrado no repositório. Verifique o deploy.'],
            ], 422);
        }

        $year = (int) $request->input('year');
        $players = $request->input('players');
        $results = [];

        $requestId = uniqid('req_');
        $totalPlayers = count($players);

        Log::channel('earnings')->info("[EARNINGS][{$requestId}] Iniciando envio de relatórios — Ano: {$year}, Total de jogadores: {$totalPlayers}, Arquivo: {$filePath}");

        foreach ($players as $index => $player) {
            $seq = $index + 1;
            $playerData = $this->service->findPlayerById($filePath, $player['player_id']);

            if ($playerData === null) {
                Log::channel('earnings')->warning("[EARNINGS][{$requestId}] [{$seq}/{$totalPlayers}] Jogador NÃO ENCONTRADO na planilha — Player ID: {$player['player_id']}, CPF: {$player['cpf']}, Email: {$player['email']}");

                $results[] = [
                    'player_id' => $player['player_id'],
                    'cpf' => $player['cpf'],
                    'email' => $player['email'],
                    'status' => 'not_found',
                ];

                continue;
            }

            $playerData['email'] = $player['email'];
            $playerData['cpf'] = $player['cpf'];

            SendPlayerEarningsEmailJob::dispatch($playerData, $year);

            Log::channel('earnings')->info("[EARNINGS][{$requestId}] [{$seq}/{$totalPlayers}] Job de envio DESPACHADO — Player ID: {$player['player_id']}, Username: {$playerData['username']}, CPF: {$player['cpf']}, Email: {$player['email']}, Net Income: {$playerData['net_income']}");

            $results[] = [
                'player_id' => $player['player_id'],
                'cpf' => $player['cpf'],
                'email' => $player['email'],
                'status' => 'sent',
            ];
        }

        $sent = count(array_filter($results, fn ($r) => $r['status'] === 'sent'));
        $notFound = count($results) - $sent;

        Log::channel('earnings')->info("[EARNINGS][{$requestId}] Envio finalizado — Despachados: {$sent}, Não encontrados: {$notFound}");

        return response()->json([
            'message' => "{$sent} relatório(s) enviado(s) para a fila." . ($notFound > 0 ? " {$notFound} jogador(es) não encontrado(s)." : ''),
            'results' => $results,
        ]);
    }

    /**
     * Export a PDF report of sent earnings emails based on the log file.
     */
    public function exportLog(): Response
    {
        $logPath = storage_path('logs/earnings-emails.log');

        $entries = [];

        if (file_exists($logPath)) {
            $handle = fopen($logPath, 'r');

            while (($line = fgets($handle)) !== false) {
                // Match SUCCESS lines: contain "SUCESSO" with player details
                if (str_contains($line, 'SUCESSO')) {
                    $entry = $this->parseSuccessLine($line);
                    if ($entry) {
                        $entries[] = $entry;
                    }

                    continue;
                }

                // Match FAILURE lines: contain "FALHA DEFINITIVA"
                if (str_contains($line, 'FALHA DEFINITIVA')) {
                    $entry = $this->parseFailureLine($line);
                    if ($entry) {
                        $entries[] = $entry;
                    }
                }
            }

            fclose($handle);
        }

        $summary = [
            'total' => count($entries),
            'success' => count(array_filter($entries, fn ($e) => $e['status'] === 'success')),
            'failed' => count(array_filter($entries, fn ($e) => $e['status'] === 'failed')),
            'generated_at' => now()->format('d/m/Y H:i:s'),
        ];

        $pdf = Pdf::loadView('reports.earnings-email-log', [
            'entries' => $entries,
            'summary' => $summary,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('relatorio-emails-earnings-' . now()->format('Y-m-d_His') . '.pdf');
    }

    private function parseSuccessLine(string $line): ?array
    {
        // Extract timestamp
        $timestamp = null;
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2})/', $line, $m)) {
            $timestamp = $m[1];
        }

        // Extract player details from SUCCESS log line
        $playerId = $this->extractField($line, 'Player ID');
        $username = $this->extractField($line, 'Username');
        $email = $this->extractField($line, 'Email');
        $year = $this->extractField($line, 'Ano');
        $messageId = $this->extractField($line, 'Message-ID');

        if (! $playerId) {
            return null;
        }

        return [
            'timestamp' => $timestamp,
            'player_id' => $playerId,
            'username' => $username,
            'email' => $email,
            'year' => $year,
            'message_id' => $messageId,
            'status' => 'success',
        ];
    }

    private function parseFailureLine(string $line): ?array
    {
        $timestamp = null;
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2})/', $line, $m)) {
            $timestamp = $m[1];
        }

        $playerId = $this->extractField($line, 'Player ID');
        $username = $this->extractField($line, 'Username');
        $email = $this->extractField($line, 'Email');
        $year = $this->extractField($line, 'Ano');

        if (! $playerId) {
            return null;
        }

        return [
            'timestamp' => $timestamp,
            'player_id' => $playerId,
            'username' => $username,
            'email' => $email,
            'year' => $year,
            'message_id' => null,
            'status' => 'failed',
        ];
    }

    private function extractField(string $line, string $field): ?string
    {
        $pattern = '/' . preg_quote($field, '/') . ':\s*([^,\n]+)/';
        if (preg_match($pattern, $line, $m)) {
            return trim($m[1]);
        }

        return null;
    }
}
