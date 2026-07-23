<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessPortfolioImage;
use App\Models\PortfolioMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessPortfolioImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_compresses_image_and_updates_model(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not installed.');
        }

        Storage::fake('public');

        $disk = Storage::disk('public');
        $image = imagecreatetruecolor(2000, 1500);
        ob_start();
        imagejpeg($image, null, 95);
        $content = ob_get_clean();
        imagedestroy($image);

        $path = 'portfolio/test-image.jpg';
        $disk->put($path, $content);

        $media = PortfolioMedia::factory()->image()->create([
            'url' => $path,
            'processed' => false,
        ]);

        $job = new ProcessPortfolioImage($media);
        $job->handle();

        $media->refresh();

        $this->assertTrue($media->processed);
        $this->assertNotNull($media->url_processed);
        $this->assertStringContainsString('portfolio/processed/', $media->url_processed);
    }

    public function test_job_handles_missing_file_gracefully(): void
    {
        Storage::fake('public');

        $media = PortfolioMedia::factory()->image()->create([
            'url' => 'portfolio/nonexistent.jpg',
            'processed' => false,
        ]);

        $job = new ProcessPortfolioImage($media);
        $job->handle();

        $media->refresh();

        $this->assertFalse($media->processed);
    }
}
