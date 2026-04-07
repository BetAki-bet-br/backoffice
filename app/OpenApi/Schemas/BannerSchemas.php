<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="BannerTranslation",
 *
 *   @OA\Property(property="locale", type="string", example="pt-BR"),
 *   @OA\Property(property="title", type="string", example="Deposite e ganhe 100%"),
 *   @OA\Property(property="alt_text", type="string", example="Banner promoção 100%"),
 *   @OA\Property(property="media", type="object",
 *     @OA\Property(property="desktop", type="string", format="uri", example="https://cdn.ex/banners/pt/desk.jpg"),
 *     @OA\Property(property="mobile", type="string", format="uri", example="https://cdn.ex/banners/pt/mob.jpg")
 *   )
 * )
 *
 * @OA\Schema(
 *   schema="Banner",
 *
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="slug", type="string", example="promo-100-deposito"),
 *   @OA\Property(property="status", type="string", enum={"draft","review","scheduled","published","archived"}),
 *   @OA\Property(property="countries", type="array", @OA\Items(type="string", example="BR")),
 *   @OA\Property(property="publish_at", type="string", format="date-time", nullable=true),
 *   @OA\Property(property="expire_at", type="string", format="date-time", nullable=true),
 *   @OA\Property(property="link_url", type="string", format="uri", nullable=true),
 *   @OA\Property(property="utm_source", type="string", nullable=true),
 *   @OA\Property(property="utm_medium", type="string", nullable=true),
 *   @OA\Property(property="utm_campaign", type="string", nullable=true),
 *   @OA\Property(property="media", type="object",
 *     @OA\Property(property="desktop", type="string", format="uri", nullable=true),
 *     @OA\Property(property="mobile", type="string", format="uri", nullable=true)
 *   ),
 *   @OA\Property(property="translations", type="array", @OA\Items(ref="#/components/schemas/BannerTranslation")),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *   schema="BannerStoreRequest",
 *   allOf={
 *     @OA\Schema(ref="#/components/schemas/Banner")
 *   }
 * )
 * @OA\Schema(
 *   schema="BannerIndex",
 *
 *   @OA\Property(
 *     property="data", type="array",
 *
 *     @OA\Items(ref="#/components/schemas/Banner")
 *   ),
 *
 *   @OA\Property(property="next_page_url", type="string", nullable=true),
 *   @OA\Property(property="prev_page_url", type="string", nullable=true)
 * )
 */
final class BannerSchemas {}
