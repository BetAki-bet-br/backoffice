<?php

namespace App\Services\SoftSwiss;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Yaml;

class SoftSwissClient
{
    public function __construct(
        protected string $cdnUrl,
        protected int $timeout = 30,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            config('services.softswiss.cdn_url'),
            config('services.softswiss.timeout', 30),
        );
    }

    /**
     * Fetch and parse the YAML game list for a provider.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProviderGames(string $provider): array
    {
        $url = "{$this->cdnUrl}/{$provider}.yaml";

        Log::info("SoftSwiss: fetching {$url}");

        $response = Http::timeout($this->timeout)->get($url);
        $response->throw();

        $parsed = Yaml::parse($response->body());

        return is_array($parsed) ? $parsed : [];
    }
}
