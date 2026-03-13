<?php

namespace App\Services\BaseApi;

class ProviderSyncService
{
    public function __construct(protected BasePortalApiClient $client) {}

    public static function make(): self
    {
        return new self(BasePortalApiClient::fromConfig());
    }

    public function syncPortal(int $portalId): array
    {
        $categoryStats = CategorySyncService::make()->syncPortal($portalId);

        return [
            'fetched_games' => $categoryStats['games_synced'],
            'providers_found' => $categoryStats['providers_synced'],
            'created' => $categoryStats['created'],
            'updated' => $categoryStats['updated'],
        ];
    }
}
