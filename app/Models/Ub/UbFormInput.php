<?php

namespace App\Models\Ub;

use Illuminate\Database\Eloquent\Model;

class UbFormInput extends Model
{
    public $fillable = [
        'title',
        'name',
        'value',
        'type',
    ];
}
