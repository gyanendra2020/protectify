<?php

namespace App\Models\Ub;

use Illuminate\Database\Eloquent\Model;

class UbResource extends Model
{
    public $casts = [
        'child_resource_ids' => 'array',
    ];

    public function getAbsoluteUrlAttribute()
    {
        return resolve_url($this->url, $this->parent_resource->url);
    }

    public function page()
    {
        return $this->belongsTo(UbPage::class);
    }

    public function parent_resource()
    {
        return $this->belongsTo(UbResource::class);
    }
}
