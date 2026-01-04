<?php

namespace App\Support\Casino;

use App\Models\Domain\Casino\GameExtra;

class GameExtraResolver
{
    public static function preloadByExternalIds(array $externalIds): array
    {
        $externalIds = array_values(array_unique(array_filter($externalIds)));

        if (!$externalIds) {
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
        if (!$extra) {
            return $dto->withExtras(null, null, null);
        }

        $vol = $extra->volatility;
        if ($vol !== null) {
            $vol = (int)$vol;
            if ($vol < 1 || $vol > 5) {
                $vol = null;
            }
        }

        return $dto->withExtras(
            rtp: $extra->rtp !== null ? (string)$extra->rtp : null,
            volatility: $vol,
            minBet: $extra->min_bet !== null ? (string)$extra->min_bet : null,
        );
    }
}