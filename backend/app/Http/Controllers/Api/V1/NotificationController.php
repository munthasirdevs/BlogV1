<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\DeleteNotificationRequest;
use App\Http\Requests\Notification\ListNotificationsRequest;
use App\Http\Requests\Notification\MarkAllAsReadRequest;
use App\Http\Requests\Notification\MarkNotificationReadRequest;
use App\Http\Requests\Notification\UpdateNotificationPreferencesRequest;
use App\Http\Resources\NotificationResource;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class NotificationController
 *
 * Handles all notification-related API endpoints.
 *
 * Features:
 * - List notifications with pagination and filters
 * - Mark notifications as read/unread
 * - Delete notifications
 * - Mark all as read
 * - Get unread count
 * - Get notification statistics
 * - Manage notification preferences
 * - Real-time broadcasting support
 */
class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Get user's notifications with pagination.
     *
     * @param ListNotificationsRequest $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/notifications",
     *     summary="Get user notifications",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="read_status", in="query", @OA\Schema(type="string", enum={"read", "unread"})),
     *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=50)),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function index(ListNotificationsRequest $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $this->notificationService->getUserNotifications(
            $user,
            $request->integer('per_page', 50),
            $request->input('read_status'),
            $request->input('type')
        );

        return response()->json([
            'success' => true,
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'from' => $notifications->firstItem(),
                'to' => $notifications->lastItem(),
            ],
            'links' => [
                'first' => $notifications->url(1),
                'last' => $notifications->url($notifications->lastPage()),
                'prev' => $notifications->previousPageUrl(),
                'next' => $notifications->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get a single notification.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/notifications/{id}",
     *     summary="Get single notification",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Successful response"),
     *     @OA\Response(response=404, description="Notification not found")
     * )
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new NotificationResource($notification),
        ]);
    }

    /**
     * Mark a notification as read.
     *
     * @param MarkNotificationReadRequest $request
     * @param string $id
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/notifications/{id}/read",
     *     summary="Mark notification as read",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Successful response"),
     *     @OA\Response(response=404, description="Notification not found")
     * )
     */
    public function markAsRead(MarkNotificationReadRequest $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (!$this->notificationService->markAsRead($user, $id)) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark a notification as unread.
     *
     * @param MarkNotificationReadRequest $request
     * @param string $id
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/notifications/{id}/unread",
     *     summary="Mark notification as unread",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Successful response"),
     *     @OA\Response(response=404, description="Notification not found")
     * )
     */
    public function markAsUnread(MarkNotificationReadRequest $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (!$this->notificationService->markAsUnread($user, $id)) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as unread',
        ]);
    }

    /**
     * Delete a notification.
     *
     * @param DeleteNotificationRequest $request
     * @param string $id
     * @return JsonResponse
     *
     * @OA\Delete(
     *     path="/api/v1/notifications/{id}",
     *     summary="Delete notification",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Successful response"),
     *     @OA\Response(response=404, description="Notification not found")
     * )
     */
    public function destroy(DeleteNotificationRequest $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (!$this->notificationService->deleteNotification($user, $id)) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully',
        ]);
    }

    /**
     * Mark all notifications as read.
     *
     * @param MarkAllAsReadRequest $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/notifications/mark-all-read",
     *     summary="Mark all notifications as read",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function markAllAsRead(MarkAllAsReadRequest $request): JsonResponse
    {
        $user = $request->user();

        $count = $this->notificationService->markAllAsRead(
            $user,
            $request->input('type')
        );

        return response()->json([
            'success' => true,
            'message' => "Marked {$count} notification(s) as read",
            'data' => [
                'marked_count' => $count,
            ],
        ]);
    }

    /**
     * Get unread notification count.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/notifications/unread-count",
     *     summary="Get unread notification count",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->notificationService->getUnreadCount($user);

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count,
            ],
        ]);
    }

    /**
     * Get notification statistics.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/notifications/stats",
     *     summary="Get notification statistics",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = $this->notificationService->getStats($user);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get user's notification preferences.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/users/me/notification-preferences",
     *     summary="Get notification preferences",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $preferences = $this->notificationService->getPreferences($user);

        return response()->json([
            'success' => true,
            'data' => $preferences,
        ]);
    }

    /**
     * Update user's notification preferences.
     *
     * @param UpdateNotificationPreferencesRequest $request
     * @return JsonResponse
     *
     * @OA\Put(
     *     path="/api/v1/users/me/notification-preferences",
     *     summary="Update notification preferences",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"preferences"},
     *             @OA\Property(property="preferences", type="object",
     *                 @OA\Property(property="new_comment", type="object",
     *                     @OA\Property(property="enabled", type="boolean"),
     *                     @OA\Property(property="channels", type="array", @OA\Items(type="string"))
     *                 ),
     *                 @OA\Property(property="new_like_post", type="object",
     *                     @OA\Property(property="enabled", type="boolean"),
     *                     @OA\Property(property="channels", type="array", @OA\Items(type="string"))
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function updatePreferences(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->notificationService->updatePreferences(
            $user,
            $request->input('preferences')
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully',
            'data' => $this->notificationService->getPreferences($user),
        ]);
    }

    /**
     * Send a test notification (development only).
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/notifications/test",
     *     summary="Send test notification (dev only)",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function sendTest(Request $request): JsonResponse
    {
        $user = $request->user();

        // Only allow in local/development environment
        if (!app()->environment('local', 'development')) {
            return response()->json([
                'success' => false,
                'message' => 'Test notifications are only available in development',
            ], 403);
        }

        $this->notificationService->sendTestNotification($user);

        return response()->json([
            'success' => true,
            'message' => 'Test notification sent successfully',
        ]);
    }
}
