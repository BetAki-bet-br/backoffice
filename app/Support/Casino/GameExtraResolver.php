<?php

namespace App\Support\Casino;

use App\Models\Domain\Casino\GameExtra;

class GameExtraResolver
{
    public static function preloadByExternalIds(array $externalIds): array
    {
        $externalIds = array_values(array_unique(array_filter($externalIds)));

        if (! $externalIds) {
            return [];
        }

        return GameExtra::query()
            ->whereIn('external_id', $externalIds)
            ->get()
            ->keyBy('external_id')
            ->all();
    }

    public static function enrich(GameMainDTO $dto, ?GameExtra $extra): GameMainDTO
    {
        if (! $extra) {
            return $dto->withExtras(null, null, null);
        }

        return $dto->withExtras(
            rtp: $extra->rtp !== null ? (string) $extra->rtp : null,
            volatility: self::mapVolatility($extra->volatility),
            minBet: $extra->min_bet !== null ? (string) $extra->min_bet : null,
        );
    }

    public static function mapVolatility(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        // Se já for numérico (1-5), retorna int
        if (is_numeric($value)) {
            $v = (int) $value;

            return ($v >= 1 && $v <= 5) ? $v : null;
        }

        return match (strtolower($value)) {
            'low' => 1,
            'low_medium' => 2,
            'medium' => 3,
            'medium_high' => 4,
            'high' => 5,
            default => null,
        };
    }
}
