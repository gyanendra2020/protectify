<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Ub\UbPage;
use Illuminate\Auth\Access\HandlesAuthorization;

class UbPagePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function delete(User $user, UbPage $ubPage)
    {
        return in_array($user->role, [User::ROLE_ADMIN]);
    }
}
