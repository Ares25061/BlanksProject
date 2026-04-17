<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\EditUserRequest;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $users = User::paginate($request->per_page ?? 10, ['*'], 'page', $request->page ?? 1);
        if ($users->isEmpty()) {
            return response()->json(['error' => 'Пользователи не найдены'], 404);
        }

        return response()->json(['status'=> 'success','users' => $users], 200);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateUserRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);
        $user->refresh();
        return response()->json([
            'status'=> 'success',
            'message' => 'Пользователь создан',
            'user' => $user,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $user = User::withCount([
            'createdTests as tests_count',
            'studentGroups as groups_count',
        ])->find($id);

        if (is_null($user)) {
            return response()->json(['error' => 'Пользователь не найден'], 404);
        }
        return response()->json([
            'status'=> 'success',
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EditUserRequest $request)
    {
        $user = Auth::user();
        if (is_null($user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Пользователь не найден'
            ], 404);
        }
        $validated = $request->validated();
        $user->update([
            ...$validated,
            'email_verified_at'=> !empty($validated['email']) ? null : $user->email_verified_at,
        ]);
        return response()->json([
            'status'=> 'success',
            'message' => 'Профиль обновлён',
            'user' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, int $id)
    {
        $user = User::find($id);
        if (is_null($user)) {
            return response()->json(['error' => 'Пользователь не найден'], 404);
        }
        $this->authorize('update',$user);
        $validated = $request->validated();
        $user->update([
            ...$validated,
            'email_verified_at'=> !empty($validated['email']) ? null : $user->email_verified_at,
            'password' => !empty($validated['password']) ? Hash::make($validated['password']) : $user->password
        ]);
        return response()->json([
            'status'=> 'success',
            'message' => 'Пользователь обновлён',
            'user' => $user,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $user = User::find($id);
        if (is_null($user)) {
            return response()->json(['error' => 'Пользователь не найден'], 404);
        }
        $this->authorize('delete',$user);
        $user->delete();
        return response()->json([
            'status'=> 'success',
            'message' => 'Пользователь удалён',
        ]);
    }

    public function register(CreateUserRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);
        $token = Auth::login($user);
        return response()->json([
            'status' => 'success',
            'message' => 'Регистрация завершена',
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    public function login(LoginUserRequest $request)
    {
        $validated = $request->validated();
        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password']
        ];
        $token = Auth::attempt($credentials);
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Неверный email или пароль',
            ], 401);
        }

        $user = Auth::user();
        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Вы вышли из аккаунта',
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::user(),
            'authorization' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ]
        ]);
    }
}
