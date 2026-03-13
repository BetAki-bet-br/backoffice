<?php

namespace App\Repositories;

use App\Models\Domain\Casino\PortalGame;
use Illuminate\Database\Eloquent\Collection;

class PortalGameRepository
{
    public function byPortalId(int $portalId): Collection
    {
        return PortalGame::query()
            ->where('portal_id', $portalId)
            ->orderBy('id')
            ->get();
    }

    public function findByPortalAndExternalId(int $portalId, string $externalId): ?PortalGame
    {
        return PortalGame::query()
            ->where('portal_id', $portalId)
            ->where('external_id', $externalId)
            ->first();
    }
}
