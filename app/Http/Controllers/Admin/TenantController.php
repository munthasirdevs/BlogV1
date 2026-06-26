<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    public function index(): View
    {
        $tenants = $this->tenantService->getAllTenants();
        return view('admin.tenants.index', compact('tenants'));
    }

    public function create(): View
    {
        return view('admin.tenants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'unique:sites,domain'],
            'theme' => ['nullable', 'string', 'max:50'],
        ]);

        $tenant = $this->tenantService->create(
            $validated['name'],
            $validated['domain'],
            ['theme' => $validated['theme'] ?? 'default']
        );

        activity()->performedOn($tenant)->causedBy(auth()->user())->log('created tenant: ' . $tenant->name);

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant created successfully.');
    }

    public function show(Site $tenant): View
    {
        $stats = $this->tenantService->getStats($tenant->id);
        return view('admin.tenants.show', compact('tenant', 'stats'));
    }

    public function edit(Site $tenant): View
    {
        return view('admin.tenants.edit', compact('tenant'));
    }

    public function update(Request $request, Site $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'unique:sites,domain,' . $tenant->id],
            'theme' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $tenant->update($validated);

        activity()->performedOn($tenant)->causedBy(auth()->user())->log('updated tenant: ' . $tenant->name);

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant updated.');
    }

    public function destroy(Site $tenant): RedirectResponse
    {
        $name = $tenant->name;
        $tenant->delete();
        activity()->causedBy(auth()->user())->log('deleted tenant: ' . $name);
        return redirect()->route('admin.tenants.index')->with('success', 'Tenant deleted.');
    }

    public function suspend(Site $tenant): RedirectResponse
    {
        $this->tenantService->suspend($tenant->id);
        return redirect()->route('admin.tenants.show', $tenant)->with('success', 'Tenant suspended.');
    }

    public function activate(Site $tenant): RedirectResponse
    {
        $this->tenantService->activate($tenant->id);
        return redirect()->route('admin.tenants.show', $tenant)->with('success', 'Tenant activated.');
    }
}
