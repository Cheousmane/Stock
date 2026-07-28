<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class RoleAndPermissionController extends Controller
{
    private function authorizeUserManagement(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(TenantContext::getCompanyId());
        if (!auth()->user()->hasPermissionTo('manage_users') && !auth()->user()->hasRole('admin')) {
            abort(Response::HTTP_FORBIDDEN, 'Action non autorisée.');
        }
    }

    public function roles(): JsonResponse
    {
        $this->authorizeUserManagement();
        $companyId = auth()->user()->company_id;

        return response()->json(
            Role::where('company_id', $companyId)
                ->with('permissions')
                ->get()
                ->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name'),
                ])
        );
    }

    public function permissions(): JsonResponse
    {
        return response()->json(Permission::all()->pluck('name'));
    }
}
