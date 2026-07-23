<?php

namespace App\Jobs;

use App\Models\PortfolioMedia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessPortfolioImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public PortfolioMedia $media,
    ) {}

    public function handle(): void
    {
        $disk = Storage::disk('public');
        $path = $this->media->url;

        if (!$disk->exists($path)) {
            return;
        }

        $processedPath = str_replace(
            'portfolio/',
            'portfolio/processed/',
            $path
        );

        $disk->makeDirectory('portfolio/processed');

        $content = $disk->get($path);
        $processed = $this->compressImage($content);

        $disk->put($processedPath, $processed);

        $this->media->update([
            'url_processed' => $processedPath,
            'processed' => true,
        ]);
    }

    private function compressImage(string $content): string
    {
        $image = imagecreatefromstring($content);

        if ($image === false) {
            return $content;
        }

        $maxWidth = 1200;
        $maxHeight = 900;

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = (int) ($width * $ratio);
            $newHeight = (int) ($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled(
                $resized, $image,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $width, $height
            );

            ob_start();
            imagejpeg($resized, null, 85);
            $content = ob_get_clean();

            imagedestroy($resized);
        }

        imagedestroy($image);

        return $content;
    }

    public function failed(\Throwable $exception): void
    {
        $this->media->update(['processed' => false]);
    }
}
