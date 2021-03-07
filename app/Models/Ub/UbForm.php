<?php

namespace App\Models\Ub;

use Illuminate\Database\Eloquent\Model;

class UbForm extends Model
{
    public function inputs()
    {
        return $this->hasMany(UbFormInput::class, 'form_id');
    }
}
