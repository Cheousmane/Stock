<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    private function ensureTeamContext(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(TenantContext::getCompanyId());
    }

    private function authorizeUserManagement(): void
    {
        $this->ensureTeamContext();
        if (! auth()->user()->hasPermissionTo('manage_users') && ! auth()->user()->hasRole('admin')) {
            abort(Response::HTTP_FORBIDDEN, 'Action non autorisée.');
        }
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeUserManagement();
        $companyId = TenantContext::getCompanyId();
        $users = User::with('roles')
            ->where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->when($search = $request->input('search'), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->paginate(min($request->integer('per_page', 20), 100));

        return response()->json(UserResource::collection($users)->response()->getData(true), Response::HTTP_OK);
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $this->authorizeUserManagement();
        $user = User::create([
            'company_id' => $request->user()->company_id,
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        if ($request->filled('role')) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->company_id);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $user->assignRole($request->input('role'));
        }

        $user->load('roles');

        return response()->json(new UserResource($user), Response::HTTP_CREATED);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorizeUserManagement();
        if ($user->company_id !== TenantContext::getCompanyId()) {
            abort(Response::HTTP_FORBIDDEN);
        }
        $user->load('roles');

        return response()->json(new UserResource($user), Response::HTTP_OK);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorizeUserManagement();
        if ($user->company_id !== TenantContext::getCompanyId()) {
            abort(Response::HTTP_FORBIDDEN);
        }
        $data = $request->validated();

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        } else {
            unset($data['password']);
        }

        unset($data['role']);
        $user->update($data);

        if ($request->has('role')) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->company_id);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $user->syncRoles($request->filled('role') ? [$request->input('role')] : []);
        }

        $user->load('roles');

        return response()->json(new UserResource($user), Response::HTTP_OK);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorizeUserManagement();
        if ($user->company_id !== TenantContext::getCompanyId()) {
            abort(Response::HTTP_FORBIDDEN);
        }
        $user->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
