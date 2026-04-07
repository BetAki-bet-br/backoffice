<?php

namespace App\Jobs;

use App\Models\Domain\Banners\Banner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishScheduledBannersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 30;

    public function handle(): void
    {
        // publica os "scheduled" vencidos e ainda não expirados
        Banner::query()
            ->where('status', 'scheduled')
            ->whereNotNull('publish_at')
            ->where('publish_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expire_at')->orWhere('expire_at', '>', now());
            })
            ->orderBy('id')
            ->chunkById(200, function ($chunk) {
                foreach ($chunk as $banner) {
                    $banner->update([
                        'status' => 'published',
                        'published_by' => null,
                    ]);
                }
            });
    }
}
