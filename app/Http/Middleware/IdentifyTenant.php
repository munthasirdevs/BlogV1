<?php

namespace App\Http\Middleware;

use App\Models\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $tenant = null;

        $tenant = Site::where('domain', $host)->where('is_active', true)->first();

        if (!$tenant && $request->header('X-Tenant-ID')) {
            $tenant = Site::find($request->header('X-Tenant-ID'));
        }

        if ($tenant) {
            set_tenant_id($tenant->id);
            $request->merge(['tenant' => $tenant]);
        }

        return $next($request);
    }
}
