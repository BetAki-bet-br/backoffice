<?php

namespace App\Console\Commands;

use App\Models\Domain\Casino\GameExtra;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportGameExtras extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-game-extras {file=Games List.csv}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa dados de RTP, Volatilidade e Min Bet de um arquivo CSV';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');

        if (! File::exists($file)) {
            $this->error("Arquivo não encontrado: {$file}");

            return 1;
        }

        $this->info("Lendo arquivo: {$file}");

        $handle = fopen($file, 'r');
        if ($handle === false) {
            $this->error('Não foi possível abrir o arquivo.');

            return 1;
        }

        // Lê o cabeçalho
        $headers = fgetcsv($handle, 0, ';');

        // Mapeamento simples baseado nos nomes das colunas ou índices fixos se preferir
        // Usaremos índices fixos baseados na análise do arquivo fornecido:
        // 0: Game id -> external_id
        // 11: Volatility
        // 12: Minimum Bet
        // 21: Available RTP

        $count = 0;
        $updated = 0;
        $created = 0;

        $batchSize = 500;
        $batchData = [];

        DB::beginTransaction();

        try {
            while (($data = fgetcsv($handle, 0, ';')) !== false) {
                $count++;

                $externalId = $data[0] ?? null;
                if (! $externalId || $externalId === 'Game id') {
                    continue;
                }

                // Normaliza o ID para coincidir com portal_games (ALE-{id})
                if (! str_starts_with($externalId, 'ALE-')) {
                    $externalId = 'ALE-'.$externalId;
                }

                $volatility = $data[11] ?? null;
                $minBet = $data[12] ?? null;
                $rtp = $data[21] ?? null;

                // Limpeza básica
                if ($volatility === 'Not applicable' || $volatility === 'NOT_AVAILABLE' || $volatility === '') {
                    $volatility = null;
                }

                if ($minBet === 'Not applicable' || $minBet === 'Not available' || $minBet === '') {
                    $minBet = null;
                } else {
                    $minBet = (float) str_replace(',', '.', $minBet);
                }

                if ($rtp === 'Not applicable' || $rtp === '') {
                    $rtp = null;
                } else {
                    $rtp = (float) str_replace(',', '.', $rtp);
                }

                // Prepara para upsert
                GameExtra::updateOrCreate(
                    ['external_id' => $externalId],
                    [
                        'volatility' => $volatility,
                        'min_bet' => $minBet,
                        'rtp' => $rtp,
                        'source' => 'csv_import',
                    ]
                );

                $updated++; // updateOrCreate conta como "processado"

                if ($count % 100 == 0) {
                    $this->info("Processados: {$count}...");
                }
            }

            DB::commit();
            fclose($handle);

            $this->info('Importação concluída com sucesso!');
            $this->info("Total de linhas processadas: {$count}");
            $this->info("Registros atualizados/criados: {$updated}");

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            $this->error('Erro durante a importação: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
