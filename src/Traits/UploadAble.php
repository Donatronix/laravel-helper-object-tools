<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\InvalidBase64Data;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * UploadAble trait to upload and retrieve file uploads
 */
trait UploadAble
{
    use FileUploader;

    /**
     * Add a file to the medialibrary. The file will be removed from
     * its original location.
     *
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function uploadMedia(string|UploadedFile $file, string $collection, ?string $filename = null): Media
    {
        $collection = $collection ?? $this->collectionName;

        return $this->addMedia($file)
            ->usingName($filename ?? Str::random(40))
            ->toMediaCollection($collection);
    }

    /**
     * Add a file from the given disk.
     *
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function uploadMediaFromDisk(string $key, string $collection, ?string $filename = null, string $disk = 'public'): Media
    {
        $collection = $collection ?? $this->collectionName;

        return $this->addMediaFromDisk($key, $disk)
            ->usingName($filename ?? Str::random(40))
            ->toMediaCollection($collection);
    }

    /**
     * Add a remote file to the medialibrary.
     *
     * @throws FileCannotBeAdded
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function uploadMediaFromUrl(string $url, string $collection, ?string $filename = null): Media
    {
        $collection = $collection ?? $this->collectionName;

        return $this->addMediaFromUrl($url)
            ->usingName($filename ?? Str::random(40))
            ->toMediaCollection($collection);
    }

    /**
     * Add file from the current request to the medialibrary.
     *
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function uploadMediaFromRequest(string $keyName, string $collection, ?string $filename = null): Media
    {
        $collection = $collection ?? $this->collectionName;

        return $this->addMediaFromRequest($keyName)
            ->usingName($filename ?? Str::random(40))
            ->toMediaCollection($collection);
    }

    /**
     * Add multiple files from the current request to the medialibrary.
     *
     * @param array<string> $keys
     */
    public function uploadMultipleMediaFromRequest(array $keys, string $collection, ?string $filename = null): Collection
    {
        $collection = $collection ?? $this->collectionName;

        return $this->addMultipleMediaFromRequest($keys)
            ->usingName($filename ?? Str::random(40))
            ->each(static function ($fileAdder) use ($collection): void {
                $fileAdder->toMediaCollection($collection);
            });
    }

    /**
     * Add multiple files from the current request to the medialibrary.
     */
    public function uploadAllMediaFromRequest(string $collection, ?string $filename = null): Collection
    {
        $collection = $collection ?? $this->collectionName;

        return $this->addAllMediaFromRequest()
            ->usingName($filename ?? Str::random(40))
            ->each(static function ($fileAdder) use ($collection): void {
                $fileAdder->toMediaCollection($collection);
            });
    }

    /**
     * Add a base64 encoded file to the media library.
     *
     * @throws FileCannotBeAdded
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     * @throws InvalidBase64Data
     */
    public function uploadMediaFromBase64(string $base64data, string $collection, ?string $allowedMimeTypes = null, ?string $filename = null): Media
    {
        $collection = $collection ?? $this->collectionName;

        return $this->addMediaFromBase64($base64data, $allowedMimeTypes)
            ->usingName($filename ?? Str::random(40))
            ->toMediaCollection($collection);
    }

    /**
     * Copy a file to the media library.
     *
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function copyMediaFile(string|UploadedFile $file, string $collection): Media
    {
        $collection = $collection ?? $this->collectionName;

        return $this->copyMedia($file)
            ->toMediaCollection($collection);
    }

    /**
     * Get files from folder and upload
     */
    public function getFiles(string $directory): Collection
    {
        $directory = 'ajax/'.$directory;

        return collect(File::Files($directory))
            ->sortBy(static function ($file) {
                return $file->getMTime();
            });
    }

    /**
     * Check if folder exists
     */
    public function folderExists(string $directory): bool
    {
        return File::isDirectory('ajax/'.$directory);
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function attachMedia(string $directory, string $collection): void
    {
        $collection = $collection ?? $this->collectionName;
        foreach ($this->getFiles($directory) as $file) {
            $this->uploadMedia($file->getPathname(), $collection);
        }
    }

    /**
     * Delete media item
     */
    public function deleteMediaItem(Media|int|null $media = null): mixed
    {
        if (is_null($media)) {
            $mediaItems = $this->getMedia();
            foreach ($mediaItems as $mediaItem) {
                $mediaItem->delete();
            }

            return true;
        }

        if (is_numeric($media)) {
            $media = Media::find($media);
        }

        return $media->delete();
    }

    /**
     * Get cover image
     */
    public function getCoverImageAttribute(): string
    {
        return $this->getFirstMediaUrl('coverImage');
    }

    /**
     * Retrieve files from the medialibrary.
     */
    public function getUploads(?string $collection = null): mixed
    {
        $collection = $collection ?? $this->collectionName;

        return $this->getMedia($collection);
    }
}
