<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *   path="/api/v1/auth/login",
     *   tags={"Auth"},
     *   summary="Autenticar via email/senha e receber token Bearer",
     *
     *   @OA\RequestBody(
     *     required=true,
     *
     *     @OA\JsonContent(
     *       required={"email","password"},
     *
     *       @OA\Property(property="email", type="string", format="email", example="admin@betaki.com"),
     *       @OA\Property(property="password", type="string", format="password", example="Betaki@123")
     *     )
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *
     *     @OA\JsonContent(
     *
     *       @OA\Property(property="token", type="string"),
     *       @OA\Property(property="token_type", type="string", example="Bearer"),
     *       @OA\Property(property="expires_at", type="string", format="date-time")
     *     )
     *   ),
     *
     *   @OA\Response(response=401, description="Credenciais inválidas")
     * )
     */
    public function login(LoginRequest $request)
    {
        $user = \App\Models\User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => ['code' => 'INVALID_CREDENTIALS', 'message' => 'Credenciais inválidas.'],
            ], 401);
        }

        $abilities = ['*'];
        $expiresAt = now()->addDays(60);

        $token = $user->createToken('admin-panel', $abilities, $expiresAt);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/auth/me",
     *   tags={"Auth"},
     *   security={{"bearerAuth": {}}},
     *   summary="Dados do usuário autenticado",
     *
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *
     *     @OA\JsonContent(
     *
     *       @OA\Property(property="id", type="integer"),
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="email", type="string", format="email")
     *     )
     *   ),
     *
     *   @OA\Response(response=401, description="Não autenticado")
     * )
     */
    public function me(Request $request)
    {
        return response()->json($request->user()->only(['id', 'name', 'email']));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
