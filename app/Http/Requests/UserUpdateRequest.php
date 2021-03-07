<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->id === $this->route('user')->id || auth()->user()->role === User::ROLE_ADMIN;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return array_merge([
            'user' => 'required|array',
            'user.name' => 'string',
        ], auth()->user()->role === User::ROLE_ADMIN ? [
            'user.role' => 'string|in:' . implode(',', User::$roles) . '|nullable',
            'user.is_disabled' => 'boolean',
        ] : []);
    }
}
