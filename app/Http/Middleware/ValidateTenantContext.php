<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = tenant_id();

        if ($request->is('admin/*') && !$tenantId) {
            Log::warning('Admin route accessed without tenant context', [
                'url' => $request->fullUrl(),
                'user_id' => auth()->id(),
            ]);
        }

        if (auth()->check() && $tenantId) {
            $userTenantId = auth()->user()->tenant_id;

            if ($userTenantId && $userTenantId !== $tenantId) {
                Log::error('Tenant mismatch detected', [
                    'user_id' => auth()->id(),
                    'user_tenant_id' => $userTenantId,
                    'request_tenant_id' => $tenantId,
                ]);

                if (!auth()->user()->hasRole('super-admin')) {
                    auth()->logout();
                    return redirect()->route('login')->with('error', 'Access denied.');
                }
            }
        }

        return $next($request);
    }
}
