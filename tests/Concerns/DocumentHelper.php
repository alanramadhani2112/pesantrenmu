<?php

namespace Tests\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait DocumentHelper
{
    protected function createFakeDocument(string $filename = 'test-document.pdf'): UploadedFile
    {
        Storage::fake('local');
        return UploadedFile::fake()->create($filename, 100);
    }

    protected function createFakeImage(string $filename = 'test-image.png'): UploadedFile
    {
        Storage::fake('local');
        return UploadedFile::fake()->image($filename, 100, 100);
    }
}
