<?php

declare(strict_types=1);

namespace LaravelHelperObjectTools\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Trait UploadFile
 */
trait UploadFile
{
    /**
     * Upload file
     */
    public function uploadFile(UploadedFile $file, ?string $folder = null, ?string $filename = null, string $disk = 'public'): false|string
    {
        $name = $filename ?? Str::random(25);
        $extension = $file->getClientOriginalExtension();
        $tempName = $name;
        while (\file_exists($folder . '/' . $tempName . '.' . $extension)) {
            if (count(\explode('_', $tempName)) === 1) {
                $tempName .= '_1';
            } else {
                $tempName++;
            }
        }
        $name = "$tempName.$extension";

        return $file->storeAs(
            $folder,
            $name,
            $disk
        );
    }

    /**
     * Delete file
     *
     * @return void
     */
    public function deleteFile(?string $path = null)
    {
        $path = str_replace(asset(''), '', $path);

        return unlink(public_path($path));
    }

    /**
     * Delete folder
     *
     * @return void
     */
    public function deleteFolder(?string $path = null)
    {
        return File::deleteDirectory(public_path($path));
    }
}
