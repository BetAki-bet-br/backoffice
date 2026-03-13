<?php

namespace App\Services\BaseApi;

use App\Models\Domain\Casino\PortalGame;

class PortalGamesSyncService
{
    public function __construct(protected BasePortalApiClient $client) {}

    public static function make(): self
    {
        return new self(BasePortalApiClient::fromConfig());
    }

    private function toStringOrNull($value): ?string
    {
        if ($value === null) {
            return null;
        }

        // string/numero/bool
        if (is_scalar($value)) {
            return (string) $value;
        }

        // array/obj -> json string
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function syncPortal(int $portalId): array
    {
        $json = $this->client->getPortalGames($portalId);

        $list = $json['gameMainList'] ?? [];
        if (! is_array($list)) {
            $list = [];
        }

        $now = now();
        $rows = [];

        foreach ($list as $g) {
            if (! is_array($g)) {
                continue;
            }

            $externalId = $g['externalId'] ?? null;
            if (! $externalId) {
                continue;
            }

            $rows[] = [
                'portal_id' => $portalId,
                'external_id' => $this->toStringOrNull($externalId),

                // Estes na sua amostra são string/null, mas deixo robusto
                'name' => $this->toStringOrNull($g['name'] ?? null),
                'product_name' => $this->toStringOrNull($g['productName'] ?? null),
                'supplier_name' => $this->toStringOrNull($g['productSupplierName'] ?? null),

                'payload' => json_encode($g, JSON_UNESCAPED_UNICODE),

                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! $rows) {
            return [
                'portal_id' => $portalId,
                'fetched' => count($list),
                'upserted' => 0,
            ];
        }

        PortalGame::upsert(
            $rows,
            ['portal_id', 'external_id'],
            ['name', 'product_name', 'supplier_name', 'payload', 'updated_at']
        );

        return [
            'portal_id' => $portalId,
            'fetched' => count($list),
            'upserted' => count($rows),
        ];
    }
}
