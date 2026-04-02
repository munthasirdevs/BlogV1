<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Class UserService
 *
 * Service class for user-related operations.
 * Handles user CRUD, role management, and profile updates.
 */
class UserService extends BaseService
{
    /**
     * Initialize the repository.
     */
    protected function initializeRepository(): void
    {
        $this->repository = new UserRepository();
    }

    /**
     * Get paginated users with filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginatedUsers(array $filters = [], int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = User::query();

        // Search filter
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // Role filter
        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        // Status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Date range filter
        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }
        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        // Sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortOrder = ($filters['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['name', 'email', 'role', 'status', 'created_at', 'updated_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->latest();
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new user.
     *
     * @param array $data
     * @return User
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Hash password
            $data['password'] = Hash::make($data['password']);

            // Generate timezone if not provided
            if (empty($data['timezone'])) {
                $data['timezone'] = 'UTC';
            }

            $user = $this->repository->create($data);

            // Assign role using Spatie Permission
            if (isset($data['role'])) {
                $this->assignRole($user, $data['role']);
            }

            return $user->load('roles');
        });
    }

    /**
     * Update a user.
     *
     * @param int $id
     * @param array $data
     * @return User
     */
    public function updateUser(int $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data) {
            $user = $this->findOrFail($id);

            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
                unset($data['password_confirmation']);
            }

            $user = $this->repository->update($id, $data);

            // Update role if provided
            if (isset($data['role'])) {
                $this->assignRole($user, $data['role']);
            }

            return $user->fresh(['roles']);
        });
    }

    /**
     * Update user profile.
     *
     * @param int $id
     * @param array $data
     * @return User
     */
    public function updateProfile(int $id, array $data): User
    {
        $allowedFields = ['name', 'bio', 'avatar', 'website', 'twitter', 'github', 
                          'linkedin', 'facebook', 'location', 'timezone'];
        
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        return $this->update($id, $updateData);
    }

    /**
     * Update user password.
     *
     * @param int $id
     * @param string $newPassword
     * @return User
     */
    public function updatePassword(int $id, string $newPassword): User
    {
        return $this->update($id, ['password' => Hash::make($newPassword)]);
    }

    /**
     * Assign role to user.
     *
     * @param User $user
     * @param string $roleName
     * @return User
     */
    public function assignRole(User $user, string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'sanctum']);
        
        $user->syncRoles([$role]);
        
        // Also update legacy role column
        $user->update(['role' => $roleName]);

        return $user->fresh(['roles']);
    }

    /**
     * Remove role from user.
     *
     * @param User $user
     * @param string $roleName
     * @return User
     */
    public function removeRole(User $user, string $roleName): User
    {
        $user->removeRole($roleName);
        
        // Update legacy role column to default
        $user->update(['role' => 'user']);

        return $user->fresh(['roles']);
    }

    /**
     * Assign multiple roles to user.
     *
     * @param User $user
     * @param array $roleNames
     * @return User
     */
    public function assignMultipleRoles(User $user, array $roleNames): User
    {
        $roles = Role::whereIn('name', $roleNames)
            ->where('guard_name', 'sanctum')
            ->get();

        $user->syncRoles($roles);

        // Update legacy role column to primary role
        $primaryRole = $roles->first()?->name ?? 'user';
        $user->update(['role' => $primaryRole]);

        return $user->fresh(['roles']);
    }

    /**
     * Ban a user.
     *
     * @param int $id
     * @param string|null $reason
     * @return User
     */
    public function banUser(int $id, ?string $reason = null): User
    {
        $user = $this->update($id, ['status' => 'banned']);

        // Log the ban reason (could be stored in a separate table)
        Log::warning('User banned', [
            'user_id' => $id,
            'reason' => $reason,
            'banned_by' => auth()->id(),
        ]);

        return $user;
    }

    /**
     * Unban a user.
     *
     * @param int $id
     * @return User
     */
    public function unbanUser(int $id): User
    {
        return $this->update($id, ['status' => 'active']);
    }

    /**
     * Verify user email.
     *
     * @param int $id
     * @return User
     */
    public function verifyEmail(int $id): User
    {
        return $this->update($id, ['email_verified_at' => now()]);
    }

    /**
     * Get user by email.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return $this->repository->findByEmail($email);
    }

    /**
     * Get user statistics.
     *
     * @param int $id
     * @return array
     */
    public function getUserStats(int $id): array
    {
        return $this->repository->getUserStats($id);
    }

    /**
     * Get active users.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->findActive();
    }

    /**
     * Get authors (users with published posts).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAuthors(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->findAuthors();
    }

    /**
     * Search users.
     *
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchUsers(string $search): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->search($search);
    }

    /**
     * Delete user (soft delete if available).
     *
     * @param int $id
     * @return bool|null
     */
    public function deleteUser(int $id): ?bool
    {
        $user = $this->findOrFail($id);

        // Prevent deleting the last admin
        if ($user->role === 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                throw new \RuntimeException('Cannot delete the last admin user.');
            }
        }

        return $this->delete($id);
    }

    /**
     * Get users by role.
     *
     * @param string $role
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersByRole(string $role): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->findByRole($role);
    }

    /**
     * Get admin users.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAdminUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->findAdmins();
    }

    /**
     * Get top contributors by post count.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTopContributors(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getUsersWithPostCount($limit);
    }

    /**
     * Update user avatar.
     *
     * @param int $id
     * @param string $avatarUrl
     * @return User
     */
    public function updateAvatar(int $id, string $avatarUrl): User
    {
        return $this->repository->updateAvatar($id, $avatarUrl);
    }

    /**
     * Check if email is available.
     *
     * @param string $email
     * @param int|null $excludeId
     * @return bool
     */
    public function isEmailAvailable(string $email, ?int $excludeId = null): bool
    {
        $query = User::where('email', $email);
        
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }
}
