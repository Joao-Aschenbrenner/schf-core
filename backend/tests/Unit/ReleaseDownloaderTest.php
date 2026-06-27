<?php

namespace Tests\Unit;

use App\Services\ReleaseDownloader;
use Tests\TestCase;

class ReleaseDownloaderTest extends TestCase
{
    protected ReleaseDownloader $downloader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->downloader = app(ReleaseDownloader::class);
    }

    public function test_download_nonexistent_version(): void
    {
        $result = $this->downloader->downloadRelease('0.0.0');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
    }

    public function test_get_downloaded_versions_returns_array(): void
    {
        $result = $this->downloader->getDownloadedVersions();
        $this->assertIsArray($result);
    }

    public function test_cleanup_old_versions_returns_array(): void
    {
        $result = $this->downloader->cleanupOldVersions(3);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('cleaned', $result);
    }

    public function test_get_downloaded_assets_empty_version(): void
    {
        $result = $this->downloader->getDownloadedAssets('0.0.0');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}