<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_users');
    }

    public function index(Request $request): View
    {
        $query = User::with('roles');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = Role::orderBy('name')->get();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:active,suspended,banned'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] ??= 'active';

        $user = User::create($validated);
        $user->assignRole($validated['role']);

        activity()->performedOn($user)->causedBy(auth()->user())->log('created user: ' . $user->name);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $user->load('roles');
        $roles = Role::orderBy('name')->get();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:active,suspended,banned'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,png,webp', 'max:2048'],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        } elseif ($request->boolean('remove_avatar')) {
            $validated['avatar'] = null;
        } else {
            unset($validated['avatar']);
        }
        unset($validated['remove_avatar']);

        $user->update($validated);
        $user->syncRoles([$validated['role']]);

        activity()->performedOn($user)->causedBy(auth()->user())
            ->withProperties(['changes' => $user->getChanges()])
            ->log('updated user: ' . $user->name);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $user->delete();

        activity()->causedBy(auth()->user())->log('deleted user: ' . $name);

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
