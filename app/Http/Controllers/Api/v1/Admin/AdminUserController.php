<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sortable = ['name', 'email', 'created_at', 'last_login_at', 'last_seen_at', 'id'];
        $sort = $request->input('sort', 'created_at');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! in_array($sort, $sortable, true)) {
            $sort = 'created_at';
        }

        $users = User::withoutGlobalScopes()
            ->with('company:id,name,slug', 'roles')
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->input('company_id'), function ($query, $companyId) {
                $query->where('company_id', $companyId);
            })
            ->when($request->input('is_super_admin') !== null, function ($query) use ($request) {
                $query->where('is_super_admin', filter_var($request->input('is_super_admin'), FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy($sort, $order)
            ->paginate(min($request->integer('per_page', 20), 100));

        $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'locale' => $user->locale,
                'is_super_admin' => $user->is_super_admin,
                'is_active' => $user->is_active,
                'is_online' => $user->isOnline(),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'last_seen_at' => $user->last_seen_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
                'company' => $user->company ? [
                    'id' => $user->company->id,
                    'name' => $user->company->name,
                    'slug' => $user->company->slug,
                ] : null,
                'roles' => $user->roles->pluck('name'),
            ];
        });

        return response()->json($users, Response::HTTP_OK);
    }

    public function suspend($id): JsonResponse
    {
        $user = User::withoutGlobalScopes()->findOrFail($id);

        if ($user->is_super_admin) {
            return response()->json(['message' => 'Impossible de suspendre un Super Admin.'], Response::HTTP_FORBIDDEN);
        }

        $user->forceFill(['is_active' => false])->save();

        return response()->json(['message' => 'Utilisateur suspendu.', 'is_active' => false], Response::HTTP_OK);
    }

    public function activate($id): JsonResponse
    {
        $user = User::withoutGlobalScopes()->findOrFail($id);
        $user->forceFill(['is_active' => true])->save();

        return response()->json(['message' => 'Utilisateur activé.', 'is_active' => true], Response::HTTP_OK);
    }
}
