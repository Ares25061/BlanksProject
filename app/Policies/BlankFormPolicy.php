<?php
namespace App\Policies;

use App\Models\User;
use App\Models\BlankForm;

class BlankFormPolicy
{
    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, BlankForm $blankForm)
    {
        return $user->id === $blankForm->test->created_by;
    }

    public function submit(User $user, BlankForm $blankForm)
    {
        return ($user->id === $blankForm->test->created_by)
            && $blankForm->status === 'generated';
    }

    public function check(User $user, BlankForm $blankForm)
    {
        return ($user->id === $blankForm->test->created_by)
            && $blankForm->status === 'submitted';
    }

    public function checkMultiple(User $user)
    {
        return true;
    }

    public function delete(User $user, BlankForm $blankForm)
    {
        return ($user->id === $blankForm->test->created_by)
            && in_array($blankForm->status, ['generated', 'checked'], true);
    }

    public function assignGrade(User $user, BlankForm $blankForm)
    {
        return ($user->id === $blankForm->test->created_by)
            && $blankForm->status === 'checked';
    }
}
