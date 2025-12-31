<?php
declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *   openapi="3.0.3",
 *
 *   @OA\Info(
 *     title="BetAki Admin API",
 *     version="1.0.0",
 *     description="API REST da Dashboard de Administração da Betaki (Laravel 12 / PHP 8.4)."
 *   ),
 *
 *   @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Ambiente atual"
 *   ),
 *
 *   @OA\Components(
 *     @OA\SecurityScheme(
 *       securityScheme="bearerAuth",
 *       type="http",
 *       scheme="bearer",
 *       bearerFormat="JWT",
 *       description="Envie: Bearer {token}"
 *     ),
 *    @OA\Schema(
 *      schema="Role",
 *      @OA\Property(property="name", type="string", example="admin"),
 *      @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="banners.view"))
 *    ),
 *   ),
 * 
 *   @OA\Tag(
 *     name="Banners",
 *     description="Gestão de banners da dashboard"
 *   )
 * )
 */
final class OpenApiSpec {}