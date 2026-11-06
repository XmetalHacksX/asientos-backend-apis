<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::with('roles', 'permissions')->get()->map(function (User $u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'roles' => $u->getRoleNames(),
                'created_at' => $u->created_at?->toIso8601String(),
            ];
        });

        return response()->json($users);
    }

    public function store(UserRequest $request): JsonResponse
    {
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ];

        $user = User::create($userData);

        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario creado exitosamente.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario actualizado exitosamente.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }
}
