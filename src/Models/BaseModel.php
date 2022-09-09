<?php

declare(strict_types=1);

namespace LaravelHelperObjectTools\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

abstract class BaseModel extends Model
{
    use SoftDeletes;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::updated(static function ($model): void {
            Artisan::call('page-cache:clear');
        });
    }

    protected static function boot(): void
    {
        parent::boot();
        self::saving(static function ($model): void {
            $table = str_replace('\\', '', Str::snake(Str::plural(class_basename(self::class))));
            if (Schema::hasColumn($table, 'slug')) {
                do {
                    $slug = Str::random(50);
                } while (!self::where('slug', $slug)->exists());
                $model->slug = $slug;
            }
        });
    }

    /**
     * Get morph class
     *
     * @throws Exception
     */
    public function getMorphClass(): void
    {
        throw new RuntimeException('The model should implement `getMorphClass`');
    }

    public function getSoftDeletingAttribute(): bool
    {
        // ... check if 'this' model uses the soft deletes trait
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this), true) && !$this->forceDeleting;
    }

    /**
     * @param bool $showTimes
     */
    public function dateFormatted(bool $showTimes = false): ?string
    {
        $format = 'd/m/Y';
        $format = $showTimes ? $format . ' H:i:s' : $format;

        if (Schema::hasColumn($this->getTable(), 'created_at')) {
            return $this->created_at->format($format);
        }

        return null;
    }

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return $this->table ?? str_replace(
            '\\',
            '',
            Str::snake(Str::plural(class_basename($this)))
        );
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
