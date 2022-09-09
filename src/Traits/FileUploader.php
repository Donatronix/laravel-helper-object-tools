<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait FileUploader
{
    /**
     * @param  null  $folder
     * @param  null  $filename
     */
    public function uploadFile(UploadedFile $file, $folder = null, $filename = null, string $disk = 'public'): false|string
    {
        $name = $filename ?? Str::random(25);
        $ext = $file->getClientOriginalExtension();
        $tempName = $name;
        while (\file_exists(storage_path("$folder/$tempName.$ext"))) {
            if (count(\explode('_', $tempName)) === 1) {
                $tempName .= '_1';
            } else {
                $tempName++;
            }
        }
        $name = "$tempName.$ext";

        return $file->storeAs(
            $folder,
            $name,
            $disk
        );
    }

    /**
     * @param  null  $path
     */
    public function deleteFile($path)
    {
        $path = str_replace(storage_path(''), '', $path);

        return unlink(storage_path($path));
    }

    /**
     * @param  null  $folder
     * @param  null  $filename
     */
    public function uploadOne(UploadedFile $file, $folder = null, string $disk = 'public', $filename = null): false|string
    {
        $name = ! is_null($filename) ? $filename : Str::random(25);
        $ext = $file->getClientOriginalExtension();
        $tempName = $name;
        while (\file_exists(storage_path("$folder/$tempName.$ext"))) {
            if (count(\explode('_', $tempName)) === 1) {
                $tempName .= '_1';
            } else {
                $tempName++;
            }
        }
        $name = "$tempName.$ext";

        return $file->storeAs(
            $folder,
            $name,
            $disk
        );
    }

    /**
     * @param  null  $path
     */
    public function deleteOne($path = null, string $disk = 'public'): void
    {
        Storage::disk($disk)->delete($path);
    }
}
