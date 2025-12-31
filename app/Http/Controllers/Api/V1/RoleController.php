<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\RoleRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use OpenApi\Annotations as OA;

class RoleController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/v1/roles",
     *   tags={"RBAC"},
     *   security={{"bearerAuth": {}}},
     *   summary="Listar roles",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        $q = Role::query()
            ->when($request->filled('q'), fn($qq) =>
                $qq->where('name', 'ilike', '%'.$request->q.'%')
            )
            ->with('permissions');

        // cursor paginate para listas grandes
        return response()->json($q->cursorPaginate(20));
    }

    /**
     * @OA\Post(
     *   path="/api/v1/roles",
     *   tags={"RBAC"},
     *   security={{"bearerAuth": {}}},
     *   summary="Criar role",
     *   @OA\RequestBody(required=true,
     *     @OA\JsonContent(
     *       required={"name"},
     *       @OA\Property(property="name", type="string", example="marketing"),
     *       @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="banners.publish"))
     *     )
     *   ),
     *   @OA\Response(response=201, description="Criado")
     * )
     */
    public function store(RoleRequest $request)
    {
        $role = \DB::transaction(function () use ($request) {
            $role = Role::create($request->only('name','guard_name'));

            if ($perms = $request->input('permissions')) {
                $perms = Permission::whereIn('name', $perms)->where('guard_name', $role->guard_name)->get();
                $role->syncPermissions($perms);
            }

            return $role->load('permissions');
        });

        return response()->json($role, 201);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/roles/{id}",
     *   tags={"RBAC"},
     *   security={{"bearerAuth": {}}},
     *   summary="Obter role por ID",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(Role $role)
    {
        return response()->json($role->load('permissions'));
    }

    /**
     * @OA\Put(
     *   path="/api/v1/roles/{id}",
     *   tags={"RBAC"},
     *   security={{"bearerAuth": {}}},
     *   summary="Atualizar role",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string", example="content"),
     *       @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="banners.update"))
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function update(RoleRequest $request, Role $role)
    {
        $role = \DB::transaction(function () use ($request, $role) {
            $role->update($request->only('name','guard_name'));

            if ($request->has('permissions')) {
                $perms = Permission::whereIn('name', (array) $request->permissions)
                    ->where('guard_name', $role->guard_name)->get();
                $role->syncPermissions($perms);
            }

            return $role->load('permissions');
        });

        return response()->json($role);
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/roles/{id}",
     *   tags={"RBAC"},
     *   security={{"bearerAuth": {}}},
     *   summary="Remover role",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Sem conteúdo")
     * )
     */
    public function destroy(Role $role)
    {
        if ($role->name === 'admin') {
            return response()->json([
                'error' => [
                    'code' => 'ROLE_IMMUTABLE',
                    'message' => 'A role admin não pode ser removida.',
                ],
            ], 422);
        }

        $role->delete();
        return response()->noContent();
    }
}