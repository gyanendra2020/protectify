<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserAutocompleteRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'query' => 'required|string',
        ];
    }
}
