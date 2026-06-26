<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Services\PluginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PluginController extends Controller
{
    public function __construct(
        protected PluginService $pluginService
    ) {}

    public function index(): View
    {
        $plugins = $this->pluginService->getAllPlugins();
        return view('admin.plugins.index', compact('plugins'));
    }

    public function show(Plugin $plugin): View
    {
        $plugin->load('versions');
        return view('admin.plugins.show', compact('plugin'));
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:200'],
            'version' => ['required', 'string', 'max:50'],
            'author' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'provider_class' => ['nullable', 'string', 'max:500'],
        ]);

        $plugin = $this->pluginService->register($validated);

        return redirect()->route('admin.plugins.index')
            ->with('success', "Plugin '{$plugin->name}' registered.");
    }

    public function enable(Plugin $plugin): RedirectResponse
    {
        $this->pluginService->enable($plugin->id);
        return back()->with('success', "Plugin '{$plugin->name}' enabled.");
    }

    public function disable(Plugin $plugin): RedirectResponse
    {
        $this->pluginService->disable($plugin->id);
        return back()->with('success', "Plugin '{$plugin->name}' disabled.");
    }
}
