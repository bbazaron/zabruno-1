<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CheckoutItem extends Model
{
    protected $fillable = [
        'name',
        'price',
        'description',
        'image',
        'gender',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function media(): HasMany
    {
        return $this->hasMany(CheckoutItemMedia::class)->orderBy('sort_order')->orderBy('id');
    }

    public function resolvedName(): string
    {
        return trim((string) $this->name);
    }

    public function resolvedPrice(): string
    {
        return number_format((float) $this->price, 2, '.', '');
    }

    public function resolvedGender(): string
    {
        $own = trim((string) $this->gender);

        return in_array($own, ['boys', 'girls', 'all'], true) ? $own : 'all';
    }

    public function resolvedImageUrl(): ?string
    {
        $this->loadMissing('media');
        $first = $this->media->first();
        if ($first !== null) {
            $fromMedia = $this->toPublicMediaUrl($first->path);
            if ($fromMedia !== null) {
                return $fromMedia;
            }
        }

        return $this->toPublicMediaUrl($this->image);
    }

    /**
     * @return list<array{url: string, catalog_zoom: int, catalog_pan_x: int, catalog_pan_y: int}>
     */
    public function publicMedia(): array
    {
        $this->loadMissing('media');
        $items = [];
        foreach ($this->media as $media) {
            $url = $this->toPublicMediaUrl($media->path);
            if ($url === null) {
                continue;
            }
            $items[] = [
                'url' => $url,
                'catalog_zoom' => $this->clampZoom($media->catalog_zoom),
                'catalog_pan_x' => $this->clampPan($media->catalog_pan_x),
                'catalog_pan_y' => $this->clampPan($media->catalog_pan_y),
            ];
        }

        if ($items !== []) {
            return $items;
        }

        $fallback = $this->toPublicMediaUrl($this->image);
        if ($fallback === null) {
            return [];
        }

        return [[
            'url' => $fallback,
            'catalog_zoom' => 100,
            'catalog_pan_x' => 0,
            'catalog_pan_y' => 0,
        ]];
    }

    public function toPublicMediaUrl(?string $path): ?string
    {
        $path = trim((string) ($path ?? ''));
        if ($path === '' || $path === '0' || strtolower($path) === 'null') {
            return null;
        }
        if (str_starts_with($path, '/storage/')) {
            return $path;
        }
        if (str_starts_with($path, 'storage/')) {
            return '/'.$path;
        }
        if (preg_match('/^https?:\/\//i', $path) === 1) {
            return $path;
        }

        return '/storage/'.ltrim($path, '/');
    }

    private function clampZoom(mixed $value): int
    {
        $zoom = (int) $value;
        if ($zoom < 100) {
            return 100;
        }
        if ($zoom > 250) {
            return 250;
        }

        return $zoom;
    }

    private function clampPan(mixed $value): int
    {
        $pan = (int) $value;
        if ($pan < -100) {
            return -100;
        }
        if ($pan > 100) {
            return 100;
        }

        return $pan;
    }
}
