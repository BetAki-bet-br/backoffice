<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SendPlayerEarningsEmailJob;
use App\Services\EarningsReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EarningsReportController extends Controller
{
    public function __construct(
        private readonly EarningsReportService $service,
    ) {}

    /**
     * Upload the earnings xlsx file for later use.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx'],
        ]);

        $dir = storage_path('app/earnings');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $request->file('file')->move($dir, 'current.xlsx');

        return response()->json([
            'message' => 'Arquivo de earnings enviado com sucesso.',
            'file' => 'current.xlsx',
            'uploaded_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Check if an earnings file is currently uploaded.
     */
    public function status(): JsonResponse
    {
        $path = EarningsReportService::storagePath();

        if (! file_exists($path)) {
            return response()->json(['uploaded' => false]);
        }

        return response()->json([
            'uploaded' => true,
            'file' => 'current.xlsx',
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
            return response()->json([
                'error' => ['message' => 'Nenhum arquivo de earnings foi enviado. Faça o upload primeiro.'],
            ], 422);
        }

        $year = (int) $request->input('year');
        $players = $request->input('players');
        $results = [];

        foreach ($players as $player) {
            $playerData = $this->service->findPlayerById($filePath, $player['player_id']);

            if ($playerData === null) {
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

            $results[] = [
                'player_id' => $player['player_id'],
                'cpf' => $player['cpf'],
                'email' => $player['email'],
                'status' => 'sent',
            ];
        }

        $sent = count(array_filter($results, fn ($r) => $r['status'] === 'sent'));
        $notFound = count($results) - $sent;

        return response()->json([
            'message' => "{$sent} relatório(s) enviado(s) para a fila.".($notFound > 0 ? " {$notFound} jogador(es) não encontrado(s)." : ''),
            'results' => $results,
        ]);
    }
}
