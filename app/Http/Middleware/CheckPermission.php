<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for checking user permissions at route level.
 *
 * This middleware provides a consistent way to protect routes with permissions.
 * It supports both single permission and multiple permission checks with
 * configurable logic (any/all).
 *
 * Usage examples:
 * - Single permission: ->middleware('permission:lihat_produk')
 * - Multiple (any): ->middleware('permission:lihat_produk|kelola_produk')
 * - Multiple (all): ->middleware('permission:lihat_produk,kelola_produk')
 * - With logic: ->middleware('permission:lihat_produk|kelola_produk,all')
 *
 * @see P-002 fix in PERMISSIONREPORT.md
 */
class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string  $permissions  Permission string (pipe-separated for OR, comma for AND)
     * @param  string|null  $logic  'any' (OR) or 'all' (AND), defaults to 'any'
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $permissions, ?string $logic = null): Response
    {
        $user = $request->user();

        // Must be authenticated
        if (! $user) {
            return $this->unauthenticated($request);
        }

        // Super Admin bypass - check via config for consistency
        $superAdminRole = config('roles.super_admin_role', 'Super Admin');
        if ($user->hasRole($superAdminRole)) {
            return $next($request);
        }

        // Parse permissions
        $permissionArray = $this->parsePermissions($permissions);
        $checkLogic = $logic ?? $this->determineLogic($permissions);

        // Check permissions
        $hasPermission = $this->checkPermissions($user, $permissionArray, $checkLogic);

        if (! $hasPermission) {
            $this->logUnauthorizedAccess($request, $user, $permissionArray);

            return $this->unauthorized($request, $permissionArray);
        }

        return $next($request);
    }

    /**
     * Parse permission string into array.
     *
     * @param  string  $permissions
     * @return array
     */
    protected function parsePermissions(string $permissions): array
    {
        // Support both pipe (|) and comma (,) as separators
        // Pipe means OR, comma means AND
        if (str_contains($permissions, '|')) {
            return array_filter(explode('|', $permissions));
        }

        return array_filter(explode(',', $permissions));
    }

    /**
     * Determine the logic based on the separator used.
     *
     * @param  string  $permissions
     * @return string 'any' or 'all'
     */
    protected function determineLogic(string $permissions): string
    {
        // If using pipe separator, default to 'any' (OR)
        if (str_contains($permissions, '|')) {
            return 'any';
        }

        // If using comma separator, default to 'all' (AND)
        if (str_contains($permissions, ',')) {
            return 'all';
        }

        // Single permission, doesn't matter
        return 'any';
    }

    /**
     * Check if user has the required permissions.
     *
     * @param  mixed  $user
     * @param  array  $permissions
     * @param  string  $logic
     * @return bool
     */
    protected function checkPermissions($user, array $permissions, string $logic): bool
    {
        if (empty($permissions)) {
            return true;
        }

        if ($logic === 'all') {
            return $user->hasAllPermissions($permissions);
        }

        return $user->hasAnyPermission($permissions);
    }

    /**
     * Log unauthorized access attempt.
     *
     * @param  Request  $request
     * @param  mixed  $user
     * @param  array  $permissions
     * @return void
     */
    protected function logUnauthorizedAccess(Request $request, $user, array $permissions): void
    {
        Log::warning('Unauthorized access attempt - missing permission', [
            'event' => 'permission_denied',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'required_permissions' => $permissions,
            'route' => $request->route()?->getName(),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Return appropriate response for unauthenticated user.
     *
     * @param  Request  $request
     * @return Response
     */
    protected function unauthenticated(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi Anda telah berakhir. Silakan login kembali.',
                'error' => 'unauthenticated',
            ], 401);
        }

        return redirect()->route('login');
    }

    /**
     * Return appropriate response for unauthorized user.
     *
     * @param  Request  $request
     * @param  array  $permissions
     * @return Response
     */
    protected function unauthorized(Request $request, array $permissions): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke halaman ini.',
                'error' => 'forbidden',
                'required_permissions' => $permissions,
            ], 403);
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
