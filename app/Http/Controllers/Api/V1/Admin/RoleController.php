<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\RoleRequest;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();

        return response()->json([
            'roles' => $roles,
            'available_permissions' => $permissions,
        ]);
    }

    public function store(RoleRequest $request): JsonResponse
    {
        $role = Role::create(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Rol creado exitosamente.',
            'role' => $role->load('permissions'),
        ], 201);
    }

    public function show(Role $role): JsonResponse
    {
        return response()->json($role->load('permissions'));
    }

    public function update(RoleRequest $request, Role $role): JsonResponse
    {
        $role->update(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Rol actualizado exitosamente.',
            'role' => $role->load('permissions'),
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        if (in_array($role->name, ['admin', 'taquilla', 'validador'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pueden eliminar los roles del sistema base.',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Rol eliminado correctamente.',
        ]);
    }
}
