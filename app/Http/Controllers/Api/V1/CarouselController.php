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

class CarouselController extends Controller
{
    /**
     * Display a listing of the resource for admin.
     */
    public function index(): JsonResponse
    {
        $carousels = Carousel::query()->withCount('slides')->latest()->paginate(20);

        return response()->json($carousels);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCarouselRequest $request): JsonResponse
    {
        $data = $request->validated();

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

    /**
     * Display the specified resource for public API.
     */
    public function show(string $slug): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $carousel = Carousel::where('slug', $slug)->firstOrFail();

        return CarouselSlideResource::collection($carousel->slides);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCarouselRequest $request, Carousel $carousel): JsonResponse
    {
        $data = $request->validated();

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

    /**
     * Remove the specified resource from storage.
     */
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
