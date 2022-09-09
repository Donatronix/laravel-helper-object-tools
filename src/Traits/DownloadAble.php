<?php

declare(strict_types=1);

namespace LaravelHelperObjectTools\Traits;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\Support\MediaStream;

trait DownloadAble
{
    /**
     * Download files in collection
     *
     * @param  string  $collection //name of collection
     */
    public function downloadFiles(?string $collection = null): MediaStream
    {
        // Let's get some media.
        $downloads = $this->getMedia($collection ?? $this->collection);

        // Download the files associated with the media in a streamed way.
        // No prob if your files are very large.
        return MediaStream::create(Str::random(40) . '.zip')->addMedia($downloads);
    }

    /**
     * Download file
     *
     * @param  \Spatie\MediaLibrary\MediaCollections\Models\Media  $mediaItem //media object
     */
    public function downloadFile(Media $mediaItem): Response
    {
        return response()->download($mediaItem->getPath(), $mediaItem->file_name);
    }
}
