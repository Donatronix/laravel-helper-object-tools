<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\UploadAble;
use Spatie\Image\Exceptions\InvalidManipulation;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

abstract class UploadModel extends BaseModel implements HasMedia
{
    use InteractsWithMedia;
    use UploadAble;

    /**
     * @throws InvalidManipulation
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('preview')
            ->fit(Manipulations::FIT_CROP, 300, 300);

        $this->addMediaConversion('thumb')
            ->crop('crop-center', 80, 80) // Trim or crop the image to the center for specified width and height.
            ->sharpen(10);

        $this->addMediaConversion('square')
            ->width(1920)
            ->height(800)
            ->sharpen(10);
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection($this->collectionName)
            ->singleFile()
            ->useDisk('media');
    }
}
