<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Domain\Banners\Banner;
use Illuminate\Http\Request;

class CarouselController extends Controller
{
    public function casino(Request $request)
    {
        return response()->json($this->buildSlides($request));
    }

    public function live(Request $request)
    {
        return response()->json($this->buildSlides($request));
    }

    private function buildSlides(Request $request): array
    {
        $now = now();
        $locale = $request->get('locale');

        $banners = Banner::query()
            ->where('status', 'published')
            ->where(function ($q) use ($now) {
                $q->whereNull('publish_at')
                    ->orWhere('publish_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expire_at')
                    ->orWhere('expire_at', '>=', $now);
            })
            ->with('translations')
            ->orderByDesc('id')
            ->get();

        return $banners->map(function (Banner $banner) use ($locale) {
            $translation = null;

            if ($locale) {
                $translation = $banner->translations->firstWhere('locale', $locale);
            }

            $translation ??= $banner->translations->first();

            $media = $translation?->media ?? $banner->media ?? [];
            $imageUrl = $media['desktop'] ?? $media['mobile'] ?? null;
            if (!$imageUrl) return null;

            $alt = $translation?->alt_text ?? $translation?->title ?? '';

            return [
                'href' => $banner->link_url ?? '',
                'imageUrl' => $imageUrl,
                'alt' => $alt,
            ];
        })
        ->filter()
        ->values()
        ->all();
    }
}
