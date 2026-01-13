<?php

namespace App\Services\BaseApi;

use App\Models\Domain\Casino\Provider;
use Illuminate\Support\Facades\Log;

class ProviderSyncService
{
    public function __construct(protected BasePortalApiClient $client)
    {
    }

    public static function make(): self
    {
        return new self(BasePortalApiClient::fromConfig());
    }

    public function syncPortal(int $portalId): array
    {
        $json = $this->client->getPortalGames($portalId);
        $list = $json['gameMainList'] ?? [];

        if (!is_array($list)) {
            $list = [];
        }

        Log::info('ProviderSyncService: Games fetched', [
            'portalId' => $portalId,
            'count' => count($list),
        ]);

        $providersMap = [];

        foreach ($list as $game) {
            if (!is_array($game)) continue;

            // Mapping based on user's TS code:
            // const { productId, productName } = game;
            $externalId = $game['productId'] ?? null;
            $name = $game['productName'] ?? null;

            if (!$externalId || !$name) {
                // Try fallback if productId is missing but we have supplier info?
                // The user was specific about productId. 
                // If it's missing, we skip or log?
                // Let's check if 'productSupplierName' is available as fallback?
                // But let's stick to the instruction "assim como em categories ... based on TS code".
                continue;
            }

            $idStr = (string)$externalId;

            if (!isset($providersMap[$idStr])) {
                $providersMap[$idStr] = [
                    'external_id' => $idStr,
                    'name' => $name,
                    'game_count' => 0,
                ];
            }

            $providersMap[$idStr]['game_count']++;
        }

        $stats = [
            'fetched_games' => count($list),
            'providers_found' => count($providersMap),
            'created' => 0,
            'updated' => 0,
        ];

        foreach ($providersMap as $data) {
            $provider = Provider::where('external_id', $data['external_id'])->first();

            if (!$provider) {
                Provider::create([
                    'external_id' => $data['external_id'],
                    'name' => $data['name'],
                    'game_count' => $data['game_count'],
                    'status' => 'active',
                ]);
                $stats['created']++;
            } else {
                $provider->update([
                    'name' => $data['name'], // Update name if changed
                    'game_count' => $data['game_count'],
                ]);
                $stats['updated']++;
            }
        }

        return $stats;
    }
}
