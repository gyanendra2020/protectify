<?php

namespace App\Http\Resources\Ub;

use Illuminate\Http\Resources\Json\JsonResource;

class UbEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
