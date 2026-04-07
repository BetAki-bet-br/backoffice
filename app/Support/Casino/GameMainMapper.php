<?php

namespace App\Support\Casino;

use App\Models\Domain\Casino\PortalGame;

class GameMainMapper
{
    public static function fromPortalGame(PortalGame $portalGame): GameMainDTO
    {
        $p = $portalGame->payload;

        // Em caso de payload vindo como string por algum motivo
        if (is_string($p)) {
            $p = json_decode($p, true);
        }
        if (! is_array($p)) {
            $p = [];
        }

        $externalId = (string) ($p['externalId'] ?? $portalGame->external_id);

        return new GameMainDTO(
            id: self::intOrNull($p['id'] ?? null),
            externalId: $externalId,

            productId: self::intOrNull($p['productId'] ?? null),
            productName: self::strOrNull($p['productName'] ?? $portalGame->product_name ?? null),

            productSupplierId: self::intOrNull($p['productSupplierId'] ?? null),
            productSupplierName: self::strOrNull($p['productSupplierName'] ?? $portalGame->supplier_name ?? null),

            name: self::strOrNull($p['name'] ?? $portalGame->name ?? null),

            gameTypeId: self::intOrNull($p['gameTypeId'] ?? null),
            gameTypeName: self::strOrNull($p['gameTypeName'] ?? null),

            demoPlayRestricted: self::boolOrNull($p['demoPlayRestricted'] ?? null),
            realPlayRestricted: self::boolOrNull($p['realPlayRestricted'] ?? null),
            maintenanceModeEnabled: self::boolOrNull($p['maintenanceModeEnabled'] ?? null),

            parameters: $p['parameters'] ?? null,
        );
    }

    private static function strOrNull(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);

        return $s === '' ? null : $s;
    }

    private static function intOrNull(mixed $v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }

        return is_numeric($v) ? (int) $v : null;
    }

    private static function boolOrNull(mixed $v): ?bool
    {
        if ($v === null) {
            return null;
        }
        if (is_bool($v)) {
            return $v;
        }
        if ($v === 1 || $v === '1' || $v === 'true') {
            return true;
        }
        if ($v === 0 || $v === '0' || $v === 'false') {
            return false;
        }

        return null;
    }
}
