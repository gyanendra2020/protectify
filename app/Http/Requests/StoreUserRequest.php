<?php

namespace App\Http\Requests;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Auth\RegistersUsers;

class StoreUserRequest extends FormRequest
{

    use RegistersUsers;
    public function authorize()
    {
        return true;
    }



    public function rules()
    {
        return [
            'name' => 'required', 'string', 'max:255',
            'email' => 'required', 'string', 'email', 'max:255', 'unique:users',
            'password' => 'required', 'string', 'min:8',
        ];
    }


}
