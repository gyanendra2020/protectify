<?php

namespace App\Models\Ub;

use Illuminate\Database\Eloquent\Model;

class UbEvent extends Model
{
    public $casts = [
        'data' => 'array',
        'resource_ids' => 'array',
    ];

    public $hidden = [
        'resource_ids',
    ];

    public function page()
    {
        return $this->belongsTo(UbPage::class);
    }
}
