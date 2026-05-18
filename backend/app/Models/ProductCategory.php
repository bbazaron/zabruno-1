<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductCategory extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'label',
        'sort_order',
    ];

    public static function slugFromLabel(string $label, ?string $preferredId = null): string
    {
        $preferred = trim((string) $preferredId);
        if ($preferred !== '') {
            $slug = Str::slug($preferred, '-');
            if ($slug !== '') {
                return Str::lower($slug);
            }
        }

        $ascii = $label;
        if (function_exists('transliterator_transliterate')) {
            $ascii = transliterator_transliterate('Any-Latin; Latin-ASCII', $label) ?: $label;
        }

        $slug = Str::slug($ascii, '-');
        if ($slug !== '') {
            return Str::lower($slug);
        }

        return 'cat-'.substr(md5($label), 0, 8);
    }

    public static function nextSortOrder(): int
    {
        return (int) (static::query()->max('sort_order') ?? 0) + 1;
    }
}
