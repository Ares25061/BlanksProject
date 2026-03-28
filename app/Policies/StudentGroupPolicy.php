<?php

namespace App\Policies;

use App\Models\StudentGroup;
use App\Models\User;

class StudentGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StudentGroup $studentGroup): bool
    {
        return $user->id === $studentGroup->created_by;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, StudentGroup $studentGroup): bool
    {
        return $user->id === $studentGroup->created_by;
    }

    public function delete(User $user, StudentGroup $studentGroup): bool
    {
        return $user->id === $studentGroup->created_by;
    }
}
