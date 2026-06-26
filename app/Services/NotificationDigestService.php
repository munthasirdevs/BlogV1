<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

class NotificationDigestService
{
    public function getUnreadCount(int $userId): int
    {
        return DatabaseNotification::where('notifiable_id', $userId)
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->count();
    }

    public function getRecent(int $userId, int $limit = 10): array
    {
        return DatabaseNotification::where('notifiable_id', $userId)
            ->where('notifiable_type', User::class)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->toArray();
    }

    public function markAsRead(int $userId, ?string $notificationId = null): void
    {
        $query = DatabaseNotification::where('notifiable_id', $userId)
            ->where('notifiable_type', User::class);

        if ($notificationId) {
            $query->where('id', $notificationId);
        }

        $query->update(['read_at' => now()]);
    }

    public function getDigest(int $userId, string $period = 'daily'): array
    {
        $since = $period === 'weekly' ? now()->subWeek() : now()->subDay();

        $notifications = DatabaseNotification::where('notifiable_id', $userId)
            ->where('notifiable_type', User::class)
            ->where('created_at', '>=', $since)
            ->orderBy('created_at', 'desc')
            ->get();

        $grouped = $notifications->groupBy(fn($n) => $n->type)
            ->map(fn($items, $type) => [
                'type' => class_basename($type),
                'count' => $items->count(),
                'latest' => $items->first()->data['message'] ?? '',
            ]);

        return [
            'period' => $period,
            'total' => $notifications->count(),
            'unread' => $notifications->whereNull('read_at')->count(),
            'grouped' => $grouped->values()->toArray(),
        ];
    }

    public function getAnalytics(): array
    {
        $now = now();
        $today = $now->copy()->startOfDay();
        $thisWeek = $now->copy()->startOfWeek();
        $thisMonth = $now->copy()->startOfMonth();

        return [
            'total_notifications' => DB::table('notifications')->count(),
            'today' => DB::table('notifications')->where('created_at', '>=', $today)->count(),
            'this_week' => DB::table('notifications')->where('created_at', '>=', $thisWeek)->count(),
            'this_month' => DB::table('notifications')->where('created_at', '>=', $thisMonth)->count(),
            'unread_total' => DB::table('notifications')->whereNull('read_at')->count(),
            'by_type' => DB::table('notifications')
                ->select('type', DB::raw('COUNT(*) as count'))
                ->groupBy('type')
                ->orderByDesc('count')
                ->take(10)
                ->get()
                ->map(fn($i) => ['type' => class_basename($i->type), 'count' => $i->count]),
        ];
    }
}
