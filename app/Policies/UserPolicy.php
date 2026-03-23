<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function update(User $user, User $model)
    {
        if($user->id === $model->id){
            return Response::allow();
        }
        return Response::deny();
    }
    public function delete(User $user, User $model)
    {
        if ($user->id === $model->id) {
            return Response::allow();
        }
        return Response::deny();
    }

}
