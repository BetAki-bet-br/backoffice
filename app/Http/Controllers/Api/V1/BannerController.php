<?php

namespace App\Http\Controllers\Api\V1;

use OpenApi\Annotations as OA;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Domain\Banners\Banner;
use App\Http\Requests\Banners\BannerRequest;
use App\Services\FileUploadService;

class BannerController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/v1/banners",
     *   tags={"Banners"},
     *   security={{"bearerAuth": {}}},
     *   summary="Listar banners (cursor paginate)",
     *   @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/BannerIndex"))
     * )
     */
    public function index(Request $request)
    {
        $q = \App\Models\Domain\Banners\Banner::query()
            ->with('translations')
            ->orderByDesc('id');

        if ($request->filled('vertical')) {
            $q->where('vertical', $request->input('vertical'));
        }

        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }

        if ($request->filled('q')) {
            $q->where('slug', 'like', '%' . $request->input('q') . '%');
        }

        if ($request->filled('countries')) {
            $countries = explode(',', $request->input('countries'));
            $countries = array_map('trim', $countries);
            if (count($countries) > 0) {
                 $q->where(function($query) use ($countries) {
                    foreach ($countries as $country) {
                        $query->orWhereJsonContains('countries', $country);
                    }
                });
            }
        }

        return response()->json($q->cursorPaginate(20));
    }

    /**
     * @OA\Post(
     *   path="/api/v1/banners",
     *   tags={"Banners"},
     *   security={{"bearerAuth": {}}},
     *   summary="Criar banner",
     *   @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/BannerStoreRequest")),
     *   @OA\Response(response=201, description="Criado", @OA\JsonContent(ref="#/components/schemas/Banner"))
     * )
     */
    public function store(BannerRequest $request)
    {
        $banner = \DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['created_by'] = $request->user()->id;
            
            // Handle file upload if cover_url file is provided
            if ($request->hasFile('cover_url')) {
                $data['cover_url'] = FileUploadService::uploadBannerImage($request->file('cover_url'));
            }
            
            $banner = Banner::create($data);

            foreach (($data['translations'] ?? []) as $t) {
                $banner->translations()->create($t);
            }

            return $banner->load('translations');
        });

        return response()->json($banner, 201);
    }

    /**
     * @OA\Put(
     *   path="/api/v1/banners/{id}",
     *   tags={"Banners"},
     *   security={{"bearerAuth": {}}},
     *   summary="Atualizar banner",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/BannerStoreRequest")),
     *   @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/Banner"))
     * )
     */
    public function update(BannerRequest $request, Banner $banner)
    {
        $banner = \DB::transaction(function () use ($request, $banner) {
            $data = $request->validated();
            $data['updated_by'] = $request->user()->id;
            
            // Handle cover_url removal
            if ($request->boolean('remove_cover_url')) {
                // Delete old image if exists
                if ($banner->cover_url) {
                    FileUploadService::deleteImageByUrl($banner->cover_url);
                }
                $data['cover_url'] = null;
                unset($data['remove_cover_url']);
            }
            // Handle file upload if cover_url file is provided
            elseif ($request->hasFile('cover_url')) {
                // Delete old image if exists
                if ($banner->cover_url) {
                    FileUploadService::deleteImageByUrl($banner->cover_url);
                }
                // Upload new image
                $data['cover_url'] = FileUploadService::uploadBannerImage($request->file('cover_url'));
            }
            
            $banner->update($data);

            if (isset($data['translations'])) {
                $banner->translations()->delete();
                foreach ($data['translations'] as $t) {
                    $banner->translations()->create($t);
                }
            }

            return $banner->load('translations');
        });

        return response()->json($banner);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/banners/{id}/publish",
     *   tags={"Banners"},
     *   security={{"bearerAuth": {}}},
     *   summary="Publicar imediatamente (override de agendamento)",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/Banner"))
     * )
     */
    public function publish(Request $request, Banner $banner)
    {
        $banner->update([
            'status'       => 'published',
            'publish_at'   => now(),
            'published_by' => $request->user()->id,
        ]);

        return response()->json($banner->fresh('translations'));
    }

    public function show(Banner $banner)
    {
        return response()->json($banner->load('translations'));
    }

    public function destroy(Request $request, Banner $banner)
    {
        $banner->delete();
        return response()->noContent();
    }
}
