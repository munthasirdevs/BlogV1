<?php

namespace App\Repositories;

use App\Models\User;

/**
 * Class UserRepository
 *
 * Repository for User model operations.
 *
 * @extends BaseRepository<User>
 */
class UserRepository extends BaseRepository
{
    /**
     * Specify Model class name.
     *
     * @return string
     */
    protected function model(): string
    {
        return User::class;
    }

    /**
     * Find user by email.
     *
     * @param string $email
     * @param array $columns
     * @return User|null
     */
    public function findByEmail(string $email, array $columns = ['*']): ?User
    {
        return $this->model->where('email', $email)->first($columns);
    }

    /**
     * Find active users.
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findActive(array $columns = ['*']): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('status', 'active')->get($columns);
    }

    /**
     * Find users by role.
     *
     * @param string $role
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByRole(string $role, array $columns = ['*']): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('role', $role)->get($columns);
    }

    /**
     * Find admin users.
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findAdmins(array $columns = ['*']): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->whereIn('role', ['admin', 'editor', 'moderator'])->get($columns);
    }

    /**
     * Search users.
     *
     * @param string $search
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(string $search, array $columns = ['*']): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where(function ($query) use ($search) {
            $query->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%");
        })->get($columns);
    }

    /**
     * Get users with post count.
     *
     * @param int $limit
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersWithPostCount(int $limit = 10, array $columns = ['*']): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->withCount('posts')
            ->orderBy('posts_count', 'desc')
            ->limit($limit)
            ->get($columns);
    }

    /**
     * Get authors (users with published posts).
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findAuthors(array $columns = ['*']): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->whereHas('posts', function ($q) {
            $q->published();
        })->get($columns);
    }

    /**
     * Ban a user.
     *
     * @param int $id
     * @return bool
     */
    public function ban(int $id): bool
    {
        return $this->update($id, ['status' => 'banned']);
    }

    /**
     * Unban a user.
     *
     * @param int $id
     * @return bool
     */
    public function unban(int $id): bool
    {
        return $this->update($id, ['status' => 'active']);
    }

    /**
     * Verify user email.
     *
     * @param int $id
     * @return bool
     */
    public function verifyEmail(int $id): bool
    {
        return $this->update($id, ['email_verified_at' => now()]);
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
        return $this->update($id, ['avatar' => $avatarUrl]);
    }

    /**
     * Update user password.
     *
     * @param int $id
     * @param string $hashedPassword
     * @return User
     */
    public function updatePassword(int $id, string $hashedPassword): User
    {
        return $this->update($id, ['password' => $hashedPassword]);
    }

    /**
     * Get user statistics.
     *
     * @param int $id
     * @return array
     */
    public function getUserStats(int $id): array
    {
        $user = $this->findOrFail($id);

        return [
            'posts_count' => $user->posts()->count(),
            'published_posts_count' => $user->publishedPosts()->count(),
            'comments_count' => $user->comments()->count(),
            'likes_given_count' => $user->likes()->count(),
            'bookmarks_count' => $user->bookmarks()->count(),
        ];
    }
}
