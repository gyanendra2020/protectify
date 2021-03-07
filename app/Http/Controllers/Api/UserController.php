<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Requests\UserAutocompleteRequest;

class UserController extends Controller
{
    public function update(UserUpdateRequest $request, User $user)
    {
        $input = $request->validated();
        $user->fill($input['user']);
        $user->save();

        return new UserResource($user);
    }

    public function autocomplete(UserAutocompleteRequest $request)
    {
        $input = $request->validated();
        $userQuery = User::query();

        if (is_numeric($input['query'])) {
            $userQuery->where('id', $input['query']);
            $userQuery->take(1);
        } elseif (preg_match('/^[.0-9a-z-_]+@[.0-9a-z-_]+[.][0-9a-z-_]{2,}$/i', $input['query'])) {
            $userQuery->where('email', $input['query']);
            $userQuery->take(1);
        } else {
            $inputQueryParts = preg_split('/[-,\s@.]+/', trim($input['query']));

            foreach ($inputQueryParts as $inputQueryPart) {
                $userQuery->where('name', 'like', '%' . $inputQueryPart . '%');
            }

            $userQuery->take(10);
        }

        $users = $userQuery->get();

        return UserResource::collection($users);
    }
}
