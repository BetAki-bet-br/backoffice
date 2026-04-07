<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Carousels\StoreCarouselRequest;
use App\Http\Requests\Carousels\UpdateCarouselRequest;
use App\Http\Resources\CarouselSlideResource;
use App\Models\Domain\Carousels\Carousel;
use App\Services\CarouselSlideUploadService;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

class CarouselController extends Controller
{
    /** @OA\Get(
     *  path="/api/v1/carousels",
     *  tags={"Carousels"},
     *  security={{"bearerAuth": {}}},
     *  summary="Listar carrosséis (paginado)",
     *
     *  @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function index(): JsonResponse
    {
        $carousels = Carousel::query()->withCount('slides')->latest()->paginate(20);

        return response()->json($carousels);
    }

    /** @OA\Post(
     *  path="/api/v1/carousels",
     *  tags={"Carousels"},
     *  security={{"bearerAuth": {}}},
     *  summary="Criar carrossel com slides",
     *
     *  @OA\RequestBody(required=true),
     *
     *  @OA\Response(response=201, description="Criado")
     * ) */
    public function store(StoreCarouselRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $carousel = \DB::transaction(function () use ($data) {
            $carousel = Carousel::create($data);

            if (isset($data['slides'])) {
                foreach ($data['slides'] as $slideData) {
                    if (isset($slideData['image'])) {
                        $slideData['image_url'] = CarouselSlideUploadService::uploadImage($slideData['image']);
                    }

                    $carousel->slides()->create($slideData);
                }
            }

            return $carousel;
        });

        return response()->json($carousel->load('slides'), 201);
    }

    /** @OA\Get(
     *  path="/api/v1/carousels/{slug}",
     *  tags={"Carousels"},
     *  summary="Exibir slides do carrossel (público)",
     *
     *  @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function show(string $slug): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $carousel = Carousel::where('slug', $slug)->firstOrFail();

        return CarouselSlideResource::collection($carousel->slides);
    }

    /** @OA\Put(
     *  path="/api/v1/carousels/{carousel}",
     *  tags={"Carousels"},
     *  security={{"bearerAuth": {}}},
     *  summary="Atualizar carrossel e slides",
     *
     *  @OA\Parameter(name="carousel", in="path", required=true, @OA\Schema(type="integer")),
     *
     *  @OA\RequestBody(required=true),
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function update(UpdateCarouselRequest $request, Carousel $carousel): JsonResponse
    {
        $data = $request->validated();

        $data['updated_by'] = $request->user()->id;

        \DB::transaction(function () use ($carousel, $data) {
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }
            $carousel->update($data);

            if (isset($data['slides'])) {
                $currentSlideIds = $carousel->slides->pluck('id')->toArray();

                $submittedSlideIds = collect($data['slides'])
                    ->pluck('id')
                    ->filter(fn ($id) => ! empty($id))
                    ->toArray();

                $slidesToDeleteIds = array_diff($currentSlideIds, $submittedSlideIds);
                if (! empty($slidesToDeleteIds)) {
                    $slidesBeingDeleted = $carousel->slides()->whereIn('id', $slidesToDeleteIds)->get();
                    foreach ($slidesBeingDeleted as $slide) {
                        if ($slide->image_url) {
                            FileUploadService::deleteImageByUrl($slide->image_url);
                        }
                    }
                    $carousel->slides()->whereIn('id', $slidesToDeleteIds)->delete();
                }

                foreach ($data['slides'] as $slideData) {
                    $slideId = $slideData['id'] ?? null;

                    $slide = ($slideId && in_array($slideId, $currentSlideIds))
                        ? $carousel->slides()->find($slideId)
                        : null;

                    if (isset($slideData['image']) && $slideData['image'] instanceof \Illuminate\Http\UploadedFile) {

                        if ($slide && $slide->image_url) {
                            FileUploadService::deleteImageByUrl($slide->image_url);
                        }

                        $slideData['image_url'] = CarouselSlideUploadService::uploadImage($slideData['image']);
                    } elseif ($slide && array_key_exists('image_url', $slideData) && is_null($slideData['image_url'])) {
                        if ($slide->image_url) {
                            FileUploadService::deleteImageByUrl($slide->image_url);
                        }
                    }
                    if (isset($slideData['image'])) {
                        unset($slideData['image']);
                    }

                    if ($slide) {

                        unset($slideData['id']);
                        $slide->update($slideData);
                    } else {
                        if (isset($slideData['id'])) {
                            unset($slideData['id']);
                        }
                        $carousel->slides()->create($slideData);
                    }
                }
            }
        });

        return response()->json($carousel->fresh('slides'));
    }

    /** @OA\Delete(
     *  path="/api/v1/carousels/{carousel}",
     *  tags={"Carousels"},
     *  security={{"bearerAuth": {}}},
     *  summary="Excluir carrossel e slides",
     *
     *  @OA\Parameter(name="carousel", in="path", required=true, @OA\Schema(type="integer")),
     *
     *  @OA\Response(response=204, description="No Content")
     * ) */
    public function destroy(Carousel $carousel): JsonResponse
    {
        \DB::transaction(function () use ($carousel) {
            foreach ($carousel->slides as $slide) {
                if ($slide->image_url) {
                    FileUploadService::deleteImageByUrl($slide->image_url);
                }
            }
            $carousel->delete();
        });

        return response()->noContent();
    }
}
