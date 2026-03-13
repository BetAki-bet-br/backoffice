<?php

namespace App\Support\Casino;

use JsonSerializable;

class GameMainDTO implements JsonSerializable
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $externalId,
        public readonly ?int $productId,
        public readonly ?string $productName,
        public readonly ?int $productSupplierId,
        public readonly ?string $productSupplierName,
        public readonly ?string $name,
        public readonly ?int $gameTypeId,
        public readonly ?string $gameTypeName,
        public readonly ?bool $demoPlayRestricted,
        public readonly ?bool $realPlayRestricted,
        public readonly ?bool $maintenanceModeEnabled,
        public readonly mixed $parameters,

        // Enriquecimento (CSV / game_extras)
        public readonly ?string $rtp = null,        // decimal:2 -> string
        public readonly ?int $volatility = null,    // 1..5
        public readonly ?string $minBet = null,     // decimal:4 -> string
    ) {}

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'externalId' => $this->externalId,

            'productId' => $this->productId,
            'productName' => $this->productName,

            'productSupplierId' => $this->productSupplierId,
            'productSupplierName' => $this->productSupplierName,

            'name' => $this->name,

            'gameTypeId' => $this->gameTypeId,
            'gameTypeName' => $this->gameTypeName,

            'demoPlayRestricted' => $this->demoPlayRestricted,
            'realPlayRestricted' => $this->realPlayRestricted,
            'maintenanceModeEnabled' => $this->maintenanceModeEnabled,

            'parameters' => $this->parameters,

            'rtp' => $this->rtp,
            'volatility' => $this->volatility,
            'minBet' => $this->minBet,
        ];
    }

    public function withExtras(?string $rtp, ?int $volatility, ?string $minBet): self
    {
        return new self(
            id: $this->id,
            externalId: $this->externalId,
            productId: $this->productId,
            productName: $this->productName,
            productSupplierId: $this->productSupplierId,
            productSupplierName: $this->productSupplierName,
            name: $this->name,
            gameTypeId: $this->gameTypeId,
            gameTypeName: $this->gameTypeName,
            demoPlayRestricted: $this->demoPlayRestricted,
            realPlayRestricted: $this->realPlayRestricted,
            maintenanceModeEnabled: $this->maintenanceModeEnabled,
            parameters: $this->parameters,
            rtp: $rtp,
            volatility: $volatility,
            minBet: $minBet,
        );
    }
}
