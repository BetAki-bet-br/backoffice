<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use OpenApi\Annotations as OA;

class PermissionController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/v1/permissions",
     *   tags={"RBAC"},
     *   security={{"bearerAuth": {}}},
     *   summary="Listar permissões",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        $q = Permission::query()->where('guard_name', $request->input('guard_name','api'))
            ->when($request->filled('q'), fn($qq) =>
                $qq->where('name', 'ilike', '%'.$request->q.'%')
            )
            ->orderBy('name');

        return response()->json($q->get(['id','name','guard_name']));
    }
}