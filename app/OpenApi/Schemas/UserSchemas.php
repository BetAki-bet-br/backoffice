<?php
declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="User",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="name", type="string", example="João Almeida"),
 *   @OA\Property(property="email", type="string", example="joao@betaki.com"),
 *   @OA\Property(property="status", type="string", example="active"),
 *   @OA\Property(property="roles_list", type="array", @OA\Items(type="string", example="admin")),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *   schema="UserIndex",
 *   @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User")),
 *   @OA\Property(property="next_page_url", type="string", nullable=true),
 *   @OA\Property(property="prev_page_url", type="string", nullable=true)
 * )
 */
final class UserSchemas {}