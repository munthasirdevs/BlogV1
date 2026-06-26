<?php

if (!function_exists('tenant_id')) {
    function tenant_id(): ?int
    {
        return app()->bound('currentTenantId') ? app('currentTenantId') : null;
    }
}

if (!function_exists('set_tenant_id')) {
    function set_tenant_id(?int $id): void
    {
        app()->instance('currentTenantId', $id);
    }
}
