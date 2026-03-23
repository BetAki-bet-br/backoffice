<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SendPlayerEarningsEmailJob;
use App\Models\EarningsReportLog;
use App\Services\EarningsReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            Log::channel('earnings')->error('[EARNINGS] Arquivo earnings.xlsx não encontrado em: '.$filePath);

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
            $zeroed = false;

            if ($playerData === null) {
                $playerData = EarningsReportService::buildZeroedPlayerData($player['player_id']);
                $playerData['username'] = $player['player_id'];
                $zeroed = true;

                Log::channel('earnings')->info("[EARNINGS][{$requestId}] [{$seq}/{$totalPlayers}] Jogador NÃO ENCONTRADO na planilha — enviando relatório ZERADO — Player ID: {$player['player_id']}, CPF: {$player['cpf']}, Email: {$player['email']}");
            } else {
                Log::channel('earnings')->info("[EARNINGS][{$requestId}] [{$seq}/{$totalPlayers}] Job de envio DESPACHADO — Player ID: {$player['player_id']}, Username: {$playerData['username']}, CPF: {$player['cpf']}, Email: {$player['email']}, Net Income: {$playerData['net_income']}");
            }

            $playerData['email'] = $player['email'];
            $playerData['cpf'] = $player['cpf'];

            $log = EarningsReportLog::create([
                'player_id' => $player['player_id'],
                'cpf' => $player['cpf'],
                'email' => $player['email'],
                'username' => $playerData['username'],
                'year' => $year,
                'status' => 'queued',
                'zeroed' => $zeroed,
            ]);

            SendPlayerEarningsEmailJob::dispatch($playerData, $year, $log->id);

            $status = $zeroed ? 'sent_zeroed' : 'sent';

            $results[] = [
                'player_id' => $player['player_id'],
                'cpf' => $player['cpf'],
                'email' => $player['email'],
                'status' => $status,
            ];
        }

        $sent = count(array_filter($results, fn ($r) => $r['status'] === 'sent'));
        $sentZeroed = count(array_filter($results, fn ($r) => $r['status'] === 'sent_zeroed'));
        $totalSent = $sent + $sentZeroed;

        Log::channel('earnings')->info("[EARNINGS][{$requestId}] Envio finalizado — Despachados: {$sent}, Despachados (zerado): {$sentZeroed}");

        $message = "{$totalSent} relatório(s) enviado(s) para a fila.";
        if ($sentZeroed > 0) {
            $message .= " {$sentZeroed} com valores zerados (não encontrado(s) na planilha).";
        }

        return response()->json([
            'message' => $message,
            'results' => $results,
        ]);
    }

    /**
     * List earnings report history with optional filters.
     */
    public function history(Request $request): JsonResponse
    {
        $query = EarningsReportLog::query()->orderByDesc('created_at');

        if ($request->filled('year')) {
            $query->where('year', (int) $request->input('year'));
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'sent_zeroed') {
                $query->where('zeroed', true)->whereIn('status', ['queued', 'sent']);
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->filled('player_id')) {
            $query->where('player_id', $request->input('player_id'));
        }

        $logs = $query->cursorPaginate(20);

        return response()->json($logs);
    }

    /**
     * Resend an earnings report from a history log entry.
     */
    public function resend(EarningsReportLog $log): JsonResponse
    {
        if ($log->zeroed) {
            $playerData = EarningsReportService::buildZeroedPlayerData($log->player_id);
            $playerData['username'] = $log->username ?: $log->player_id;
        } else {
            $filePath = EarningsReportService::storagePath();
            $playerData = file_exists($filePath)
                ? $this->service->findPlayerById($filePath, $log->player_id)
                : null;

            if ($playerData === null) {
                $playerData = EarningsReportService::buildZeroedPlayerData($log->player_id);
                $playerData['username'] = $log->username ?: $log->player_id;
            }
        }

        $playerData['email'] = $log->email;
        $playerData['cpf'] = $log->cpf;

        $newLog = EarningsReportLog::create([
            'player_id' => $log->player_id,
            'cpf' => $log->cpf,
            'email' => $log->email,
            'username' => $playerData['username'],
            'year' => $log->year,
            'status' => 'queued',
            'zeroed' => $log->zeroed,
        ]);

        SendPlayerEarningsEmailJob::dispatch($playerData, $log->year, $newLog->id);

        Log::channel('earnings')->info("[EARNINGS] Reenvio despachado — Log original: {$log->id}, Novo log: {$newLog->id}, Player ID: {$log->player_id}, Email: {$log->email}, Ano: {$log->year}, Zerado: ".($log->zeroed ? 'sim' : 'não'));

        return response()->json([
            'message' => 'Relatório reenviado para a fila.',
            'log' => $newLog,
        ]);
    }
}
