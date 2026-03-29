<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Test;

class TestPolicy
{
    public function viewAny(User $user)
    {
        return true; // Все авторизованные преподаватели могут открыть страницу своих тестов
    }

    public function view(User $user, Test $test)
    {
        return $user->id === $test->created_by;
    }

    public function create(User $user)
    {
        return true; // Все авторизованные пользователи могут создавать тесты
    }

    public function update(User $user, Test $test)
    {
        return $user->id === $test->created_by ;
    }

    public function delete(User $user, Test $test)
    {
        return $user->id === $test->created_by;
    }

    public function generateBlankForms(User $user, Test $test)
    {
        return $user->id === $test->created_by;
    }
}
