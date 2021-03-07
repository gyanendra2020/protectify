<?php

namespace App\Models\Ub;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class UbVisitor extends Model
{
    public $fillable = [
        'user_agent',
    ];

    public function generateKey() {
        do {
            $this->key = Str::random(128);
        } while (self::where('key', $this->key)->exists());
    }
}
