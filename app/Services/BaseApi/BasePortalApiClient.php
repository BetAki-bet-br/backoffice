<?php

namespace App\Services\BaseApi;

use Illuminate\Support\Facades\Http;

class BasePortalApiClient
{
    public function __construct(
        protected string $baseUrl,
        protected ?string $apiKey = null,
        protected int $timeout = 20,
    ) {}

    public static function fromConfig(): self
    {
        $cfg = config('services.base_api');

        return new self(
            rtrim($cfg['url'], '/'),
            $cfg['api_key'] ?? null,
            (int) $cfg['timeout']
        );
    }

    public function getPortalGames(int $portalId): array
    {
        $url = $this->baseUrl . "/portal/v1/prod-game/games/{$portalId}";

        $request = Http::acceptJson()
            ->timeout($this->timeout);

        if ($this->apiKey) {
            $request = $request->withHeaders([
                'x-api-key' => $this->apiKey,
            ]);
        }

        $response = $request->get($url);

        $response->throw();

        return $response->json() ?? [];
    }

    public function getGameCategories(int $portalId, ?int $levelId = null): array
    {
        $url = $this->baseUrl . "/portal/v1/prod-game/game-categories/{$portalId}";

        $request = Http::acceptJson()
            ->timeout($this->timeout);

        if ($this->apiKey) {
            $request = $request->withHeaders([
                'x-api-key' => $this->apiKey,
            ]);
        }

        $queryParams = [];
        if ($levelId) {
            $queryParams['levelId'] = $levelId;
        }

        $response = $request->get($url, $queryParams);

        $response->throw();

        return $response->json() ?? [];
    }

    public function getLobby(int $portalId, ?int $levelId = null): array
    {
        $url = $this->baseUrl . "/portal/v1/prod-game/lobby";

        $request = Http::acceptJson()
            ->timeout($this->timeout);

        if ($this->apiKey) {
            $request = $request->withHeaders([
                'x-api-key' => $this->apiKey,
            ]);
        }

        $queryParams = [
            'Language' => 'pt-BR',
            'PortalId' => $portalId,
        ];

        if ($levelId) {
            $queryParams['LevelId'] = $levelId;
        }

        $response = $request->get($url, $queryParams);

        \Illuminate\Support\Facades\Log::debug('BasePortalApiClient: getLobby raw response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $response->throw();

        return $response->json() ?? [];
    }
}