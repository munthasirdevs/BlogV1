<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\SubscribeRequest;
use App\Http\Requests\Subscription\UnsubscribeRequest;
use App\Http\Requests\Subscription\UpdatePreferencesRequest;
use App\Http\Requests\Subscription\TrackEmailRequest;
use App\Models\Subscription;
use App\Models\EmailTracking;
use App\Services\SubscriptionService;
use App\Services\NewsletterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class SubscriptionController
 *
 * Controller for managing newsletter subscriptions.
 * Handles signup, confirmation, unsubscription, preferences, and tracking.
 *
 * @package App\Http\Controllers\Api\V1
 */
class SubscriptionController extends Controller
{
    /**
     * The subscription service instance.
     */
    protected SubscriptionService $subscriptionService;

    /**
     * The newsletter service instance.
     */
    protected NewsletterService $newsletterService;

    /**
     * Constructor.
     */
    public function __construct(
        SubscriptionService $subscriptionService,
        NewsletterService $newsletterService
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->newsletterService = $newsletterService;
    }

    /**
     * Subscribe to newsletter (public signup).
     *
     * @param SubscribeRequest $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/subscribe",
     *     summary="Subscribe to newsletter",
     *     tags={"Newsletter"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="preferences", type="object",
     *                 @OA\Property(property="frequency", type="string", enum={"instant", "daily", "weekly", "monthly"}),
     *                 @OA\Property(property="new_posts", type="boolean"),
     *                 @OA\Property(property="categories", type="array", @OA\Items(type="integer"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Subscription created, confirmation email sent"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        $subscription = $this->subscriptionService->subscribe(
            $request->input('email'),
            $request->getPreferences(),
            $request->user()?->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Subscription created! Please check your email to confirm.',
            'data' => [
                'email' => $subscription->email,
                'is_confirmed' => $subscription->is_confirmed,
                'subscribed_at' => $subscription->subscribed_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Confirm subscription with token.
     *
     * @param string $token
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/subscribe/confirm/{token}",
     *     summary="Confirm subscription",
     *     tags={"Newsletter"},
     *     @OA\Parameter(name="token", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Subscription confirmed"),
     *     @OA\Response(response=404, description="Invalid or expired token")
     * )
     */
    public function confirm(string $token): JsonResponse
    {
        $subscription = $this->subscriptionService->confirmSubscription($token);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired confirmation token. A new confirmation email has been sent.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Subscription confirmed successfully!',
            'data' => [
                'email' => $subscription->email,
                'confirmed_at' => $subscription->confirmed_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Unsubscribe from newsletter.
     *
     * @param UnsubscribeRequest $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/unsubscribe",
     *     summary="Unsubscribe from newsletter",
     *     tags={"Newsletter"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="token", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Unsubscribed successfully"),
     *     @OA\Response(response=404, description="Subscription not found")
     * )
     */
    public function unsubscribe(UnsubscribeRequest $request): JsonResponse
    {
        $success = $request->isUsingToken()
            ? $this->subscriptionService->unsubscribeByToken($request->input('token'))
            : $this->subscriptionService->unsubscribe($request->input('email'));

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'You have been unsubscribed. A confirmation email has been sent.',
        ]);
    }

    /**
     * Show unsubscribe confirmation page (for email links).
     *
     * @param string $token
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/unsubscribe/{token}",
     *     summary="Show unsubscribe page",
     *     tags={"Newsletter"},
     *     @OA\Parameter(name="token", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Unsubscribe page data")
     * )
     */
    public function showUnsubscribePage(string $token): JsonResponse
    {
        $subscription = Subscription::where('token', $token)->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid unsubscribe link.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'email' => $subscription->email,
                'is_subscribed' => $subscription->isActive(),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Update subscription preferences.
     *
     * @param UpdatePreferencesRequest $request
     * @return JsonResponse
     *
     * @OA\Put(
     *     path="/api/v1/subscriptions/preferences",
     *     summary="Update subscription preferences",
     *     tags={"Newsletter"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="subscription_id", type="integer"),
     *             @OA\Property(property="preferences", type="object",
     *                 @OA\Property(property="frequency", type="string", enum={"instant", "daily", "weekly", "monthly"}),
     *                 @OA\Property(property="new_posts", type="boolean"),
     *                 @OA\Property(property="weekly_digest", type="boolean"),
     *                 @OA\Property(property="categories", type="array", @OA\Items(type="integer"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Preferences updated"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updatePreferences(UpdatePreferencesRequest $request): JsonResponse
    {
        $subscriptionId = $request->getSubscriptionId() ?? $request->user()?->subscription?->id;

        if (!$subscriptionId) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription ID is required.',
            ], 400);
        }

        $subscription = $this->subscriptionService->updatePreferences(
            $subscriptionId,
            $request->getPreferences()
        );

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully.',
            'data' => [
                'id' => $subscription->id,
                'email' => $subscription->email,
                'preferences' => $subscription->preferences,
                'frequency' => $subscription->frequency,
            ],
        ]);
    }

    /**
     * List all subscriptions (admin only).
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/subscriptions",
     *     summary="List all subscriptions",
     *     tags={"Newsletter"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="List of subscriptions"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $perPage = min($request->integer('per_page', 15), 100);

        if ($search) {
            $subscriptions = $this->subscriptionService->searchSubscriptions($search, $perPage);
        } else {
            $subscriptions = $this->subscriptionService->getSubscriptions($perPage);
        }

        return response()->json([
            'success' => true,
            'data' => $subscriptions->items(),
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ],
            'links' => [
                'first' => $subscriptions->url(1),
                'last' => $subscriptions->url($subscriptions->lastPage()),
                'prev' => $subscriptions->previousPageUrl(),
                'next' => $subscriptions->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get single subscription details (admin only).
     *
     * @param int $id
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/subscriptions/{id}",
     *     summary="Get subscription details",
     *     tags={"Newsletter"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Subscription details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $subscription = $this->subscriptionService->getSubscription($id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                ...$subscription->toArray(),
                'email_history' => $subscription->trackings()
                    ->orderBy('sent_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(fn($t) => [
                        'type' => $t->email_type,
                        'subject' => $t->subject,
                        'sent_at' => $t->sent_at?->toIso8601String(),
                        'opened' => $t->wasOpened(),
                        'clicked' => $t->wasClicked(),
                    ]),
            ],
        ]);
    }

    /**
     * Delete subscription (admin only).
     *
     * @param int $id
     * @return JsonResponse
     *
     * @OA\Delete(
     *     path="/api/v1/subscriptions/{id}",
     *     summary="Delete subscription",
     *     tags={"Newsletter"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted successfully"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        if (!$this->subscriptionService->deleteSubscription($id)) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Subscription deleted successfully.',
        ]);
    }

    /**
     * Get subscriber segments (admin only).
     *
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/admin/subscribers/segments",
     *     summary="Get subscriber segments",
     *     tags={"Newsletter"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Segment counts")
     * )
     */
    public function segments(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->subscriptionService->getSegmentCounts(),
        ]);
    }

    /**
     * Get subscriber statistics (admin only).
     *
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/admin/subscribers/stats",
     *     summary="Get subscriber statistics",
     *     tags={"Newsletter"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Statistics")
     * )
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->subscriptionService->getStatistics(),
        ]);
    }

    /**
     * Track email open.
     *
     * @param TrackEmailRequest $request
     * @param int $subscriberId
     * @param int $emailId
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/v1/track/open/{subscriberId}/{emailId}",
     *     summary="Track email open",
     *     tags={"Newsletter"},
     *     @OA\Parameter(name="subscriberId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="emailId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Tracked"),
     *     @OA\Response(response=204, description="1x1 transparent pixel")
     * )
     */
    public function trackOpen(TrackEmailRequest $request, int $subscriberId, int $emailId): \Illuminate\Http\Response
    {
        $this->newsletterService->recordOpen(
            $emailId,
            $request->ip(),
            $request->userAgent()
        );

        // Return 1x1 transparent pixel
        return response()->make(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 204, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Track link click.
     *
     * @param TrackEmailRequest $request
     * @param int $subscriberId
     * @param string $linkId
     * @return \Illuminate\Http\RedirectResponse
     *
     * @OA\Get(
     *     path="/api/v1/track/click/{subscriberId}/{linkId}",
     *     summary="Track link click",
     *     tags={"Newsletter"},
     *     @OA\Parameter(name="subscriberId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="linkId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="url", in="query", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=302, description="Redirect to original URL")
     * )
     */
    public function trackClick(TrackEmailRequest $request, int $subscriberId, string $linkId)
    {
        $trackingId = $request->input('email_id');
        
        if ($trackingId) {
            $this->newsletterService->recordClick(
                (int) $trackingId,
                $request->ip(),
                $request->userAgent()
            );
        }

        // Redirect to original URL
        $url = $request->input('url', '/');
        return redirect()->to($url);
    }

    /**
     * Resend confirmation email.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/subscribe/resend",
     *     summary="Resend confirmation email",
     *     tags={"Newsletter"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Confirmation email sent"),
     *     @OA\Response(response=404, description="Subscription not found or already confirmed")
     * )
     */
    public function resendConfirmation(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $success = $this->subscriptionService->resendConfirmation($request->input('email'));

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found or already confirmed.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Confirmation email sent. Please check your inbox.',
        ]);
    }

    /**
     * Export subscriber data (GDPR).
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/subscriptions/export",
     *     summary="Export subscriber data (GDPR)",
     *     tags={"Newsletter"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Exported data"),
     *     @OA\Response(response=404, description="Subscription not found")
     * )
     */
    public function exportData(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $data = $this->subscriptionService->exportSubscriberData($request->input('email'));

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Delete subscriber data (GDPR).
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Delete(
     *     path="/api/v1/subscriptions/delete",
     *     summary="Delete subscriber data (GDPR)",
     *     tags={"Newsletter"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Deleted successfully"),
     *     @OA\Response(response=404, description="Subscription not found")
     * )
     */
    public function deleteData(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $success = $this->subscriptionService->deleteSubscriberData($request->input('email'));

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Your data has been deleted.',
        ]);
    }
}
