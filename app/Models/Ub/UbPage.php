<?php

namespace App\Models\Ub;

use DOMDocument;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\Ub\UbPageResource;
use Illuminate\Database\Eloquent\SoftDeletes;

class UbPage extends Model
{
    use SoftDeletes;

    public $attributes = [
        'duration' => 0,
    ];

    public $fillable = [
        'url',
        'title',
        'initial_state',
    ];

    public $casts = [
        'initial_state' => 'array',
    ];

    public $appends = [
        'storage_path',
    ];

    // Attributes
    // ------------------------------------------------------------ //

    public function getStoragePathAttribute()
    {
        $pathParts = [];

        for ($index = 0; true; ++$index) {
            $division = (int) floor(($this->id - 1) / pow(256, $index + 1));

            if ($division === 0) {
                break;
            }

            $pathParts[] = sprintf('%02x', $division);
        }

        $pathParts[] = substr(md5($this->id . config('app.key')), 0, 16) . sprintf('%x', $this->id);

        return implode('/', $pathParts) . '/';
    }

    // Relations
    // ------------------------------------------------------------ //

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function visitor()
    {
        return $this->belongsTo(UbVisitor::class, 'visitor_id');
    }

    public function events()
    {
        return $this->hasMany(UbEvent::class, 'page_id');
    }

    public function forms()
    {
        return $this->hasMany(UbForm::class, 'page_id');
    }

    public function formInputs()
    {
        return $this->hasMany(UbFormInput::class, 'page_id');
    }

    // Non-static methods
    // ------------------------------------------------------------ //

    public function generateKey()
    {
        do {
            $this->key = Str::random(128);
        } while (self::where('key', $this->key)->exists());
    }

    public function storeHTMLResources($rootUrl, $html)
    {
        // $resourceUrls = get_visual_resource_urls_from_html($html);

        // foreach ($resourceUrls
        // UbPageResource;

        $resources = collect($resourceUrls)->map(function ($resourceUrl) use ($rootUrl) {
            $resourceAbsoluteUrl = resolve_url($resourceUrl['url'], $rootUrl);

            return [
                'original_url' => $resource['url'],
                'absolute_url' => $resourceAbsoluteUrl,
                'local_url' => md5($resourceAbsoluteUrl) . '.' . $resource['extension'],
            ];
        });

        return $resources;
    }

}
