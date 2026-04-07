<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Requests\Users\UserRequest;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/v1/users",
     *   tags={"Users"},
     *   security={{"bearerAuth": {}}},
     *   summary="Listar usuários administrativos",
     *
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        $q = User::query()
            ->with('roles')
            ->when($request->filled('q'), fn ($qq) => $qq->where('name', 'ilike', '%'.$request->q.'%')
                ->orWhere('email', 'ilike', '%'.$request->q.'%')
            )
            ->orderByDesc('id');

        return response()->json($q->cursorPaginate(20));
    }

    /**
     * @OA\Post(
     *   path="/api/v1/users",
     *   tags={"Users"},
     *   security={{"bearerAuth": {}}},
     *   summary="Criar usuário administrativo",
     *
     *   @OA\RequestBody(
     *     required=true,
     *
     *     @OA\JsonContent(
     *       required={"name","email","password"},
     *
     *       @OA\Property(property="name", type="string", example="Bruno Souza"),
     *       @OA\Property(property="email", type="string", example="bruno@betaki.com"),
     *       @OA\Property(property="password", type="string", example="secret123"),
     *       @OA\Property(property="roles", type="array", @OA\Items(type="string", example="admin"))
     *     )
     *   ),
     *
     *   @OA\Response(response=201, description="Criado")
     * )
     */
    public function store(UserRequest $request)
    {
        $user = \DB::transaction(function () use ($request) {
            $data = $request->validated();
            $roles = $data['roles'] ?? [];
            unset($data['roles']);

            $user = User::create($data);

            if ($roles) {
                $validRoles = Role::whereIn('name', $roles)->pluck('name')->toArray();
                $user->syncRoles($validRoles);
            }

            return $user->load('roles');
        });

        return response()->json($user, 201);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/users/{id}",
     *   tags={"Users"},
     *   security={{"bearerAuth": {}}},
     *   summary="Detalhar usuário",
     *
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(User $user)
    {
        return response()->json($user->load('roles'));
    }

    /**
     * @OA\Put(
     *   path="/api/v1/users/{id}",
     *   tags={"Users"},
     *   security={{"bearerAuth": {}}},
     *   summary="Atualizar usuário administrativo",
     *
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *   @OA\RequestBody(
     *     required=true,
     *
     *     @OA\JsonContent(
     *
     *       @OA\Property(property="name", type="string", example="João Almeida"),
     *       @OA\Property(property="email", type="string", example="joao@betaki.com"),
     *       @OA\Property(property="password", type="string", example="novaSenha123"),
     *       @OA\Property(property="roles", type="array", @OA\Items(type="string", example="content"))
     *     )
     *   ),
     *
     *   @OA\Response(response=200, description="Atualizado")
     * )
     */
    public function update(UserRequest $request, User $user)
    {
        $user = \DB::transaction(function () use ($request, $user) {
            $data = $request->validated();
            $roles = $data['roles'] ?? [];
            unset($data['roles']);

            $user->update(array_filter($data, fn ($v) => $v !== null));

            if ($roles) {
                $validRoles = Role::whereIn('name', $roles)->pluck('name')->toArray();
                $user->syncRoles($validRoles);
            }

            return $user->load('roles');
        });

        return response()->json($user);
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/users/{id}",
     *   tags={"Users"},
     *   security={{"bearerAuth": {}}},
     *   summary="Remover ou desativar usuário",
     *
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *   @OA\Response(response=204, description="Removido")
     * )
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'error' => [
                    'code' => 'SELF_DELETE_FORBIDDEN',
                    'message' => 'Você não pode excluir seu próprio usuário.',
                ],
            ], 422);
        }

        $user->delete();

        return response()->noContent();
    }
}
