<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\MailgunWebhookRequest;
use App\Http\Requests\Subscription\SendgridWebhookRequest;
use App\Models\Subscription;
use App\Models\EmailTracking;
use App\Services\NewsletterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Class WebhookController
 *
 * Controller for handling email provider webhooks (Mailgun, SendGrid).
 * Handles bounce, complaint, delivery, open, and click events.
 *
 * @package App\Http\Controllers\Api\V1
 */
class WebhookController extends Controller
{
    /**
     * The newsletter service instance.
     */
    protected $newsletterService;

    /**
     * Constructor.
     */
    public function __construct(NewsletterService $newsletterService)
    {
        $this->newsletterService = $newsletterService;
    }

    /**
     * Handle Mailgun webhook events.
     *
     * @param MailgunWebhookRequest $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/webhooks/mail/bounce",
     *     summary="Handle Mailgun bounce webhook",
     *     tags={"Newsletter", "Webhooks"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Mailgun webhook payload"
     *     ),
     *     @OA\Response(response=200, description="Webhook processed"),
     *     @OA\Response(response=422, description="Invalid payload")
     * )
     */
    public function handleMailgunBounce(MailgunWebhookRequest $request): JsonResponse
    {
        $eventType = $request->getEventType();
        $email = $request->getRecipient();
        $messageId = $request->getMessageId();

        Log::info('Mailgun webhook received', [
            'event' => $eventType,
            'email' => $email,
            'message_id' => $messageId,
        ]);

        switch ($eventType) {
            case 'bounced':
                $this->processMailgunBounce($request);
                break;
            case 'complained':
                $this->processMailgunComplaint($request);
                break;
            case 'unsubscribed':
                $this->processMailgunUnsubscribe($request);
                break;
            case 'delivered':
                $this->processMailgunDelivered($request);
                break;
            case 'opened':
                $this->processMailgunOpened($request);
                break;
            case 'clicked':
                $this->processMailgunClicked($request);
                break;
        }

        return response()->json(['success' => true, 'message' => 'Webhook processed']);
    }

    /**
     * Process Mailgun bounce event.
     */
    protected function processMailgunBounce(MailgunWebhookRequest $request): void
    {
        $email = $request->getRecipient();
        $isHardBounce = $request->isHardBounce();
        $reason = $request->getBounceReason();

        $subscription = Subscription::where('email', $email)->first();

        if (!$subscription) {
            Log::warning('Bounce for unknown email', ['email' => $email]);
            return;
        }

        // Find the tracking record
        $tracking = EmailTracking::where('subscription_id', $subscription->id)
            ->orderBy('sent_at', 'desc')
            ->first();

        if ($tracking) {
            $bounceType = $isHardBounce ? EmailTracking::BOUNCE_HARD : EmailTracking::BOUNCE_SOFT;
            $this->newsletterService->recordBounce($tracking->id, $bounceType, $reason);
        } else {
            // Create tracking record for bounce
            EmailTracking::create([
                'subscription_id' => $subscription->id,
                'email_type' => 'bounced',
                'bounced_at' => now(),
                'bounce_type' => $isHardBounce ? EmailTracking::BOUNCE_HARD : EmailTracking::BOUNCE_SOFT,
                'bounce_reason' => $reason,
            ]);

            // Mark subscription as inactive for hard bounces
            if ($isHardBounce) {
                $subscription->update([
                    'is_active' => false,
                    'unsubscribed_at' => now(),
                ]);
            }
        }

        Log::info('Bounce recorded', [
            'email' => $email,
            'type' => $isHardBounce ? 'hard' : 'soft',
            'reason' => $reason,
        ]);
    }

    /**
     * Process Mailgun complaint event.
     */
    protected function processMailgunComplaint(MailgunWebhookRequest $request): void
    {
        $email = $request->getRecipient();

        $subscription = Subscription::where('email', $email)->first();

        if (!$subscription) {
            Log::warning('Complaint for unknown email', ['email' => $email]);
            return;
        }

        // Find the tracking record
        $tracking = EmailTracking::where('subscription_id', $subscription->id)
            ->orderBy('sent_at', 'desc')
            ->first();

        if ($tracking) {
            $this->newsletterService->recordComplaint($tracking->id, EmailTracking::COMPLAINT_SPAM);
        } else {
            // Create tracking record for complaint
            EmailTracking::create([
                'subscription_id' => $subscription->id,
                'email_type' => 'complained',
                'complained_at' => now(),
                'complaint_type' => EmailTracking::COMPLAINT_SPAM,
            ]);

            // Immediately unsubscribe
            $subscription->update([
                'is_active' => false,
                'unsubscribed_at' => now(),
            ]);
        }

        Log::warning('Spam complaint recorded', ['email' => $email]);
    }

    /**
     * Process Mailgun unsubscribe event.
     */
    protected function processMailgunUnsubscribe(MailgunWebhookRequest $request): void
    {
        $email = $request->getRecipient();

        $subscription = Subscription::where('email', $email)->first();

        if ($subscription) {
            $subscription->update([
                'is_active' => false,
                'unsubscribed_at' => now(),
            ]);

            Log::info('Unsubscribe via Mailgun', ['email' => $email]);
        }
    }

    /**
     * Process Mailgun delivered event.
     */
    protected function processMailgunDelivered(MailgunWebhookRequest $request): void
    {
        $email = $request->getRecipient();

        $subscription = Subscription::where('email', $email)->first();

        if (!$subscription) {
            return;
        }

        $tracking = EmailTracking::where('subscription_id', $subscription->id)
            ->orderBy('sent_at', 'desc')
            ->first();

        if ($tracking) {
            $tracking->recordDelivery();
        }
    }

    /**
     * Process Mailgun opened event.
     */
    protected function processMailgunOpened(MailgunWebhookRequest $request): void
    {
        $email = $request->getRecipient();
        $ipAddress = $request->input('ip');
        $userAgent = $request->input('user-agent');

        $subscription = Subscription::where('email', $email)->first();

        if (!$subscription) {
            return;
        }

        $tracking = EmailTracking::where('subscription_id', $subscription->id)
            ->orderBy('sent_at', 'desc')
            ->first();

        if ($tracking) {
            $tracking->recordOpen($ipAddress, $userAgent);

            // Increment campaign count if applicable
            if ($tracking->email_campaign_id) {
                $tracking->campaign?->incrementOpened();
            }
        }
    }

    /**
     * Process Mailgun clicked event.
     */
    protected function processMailgunClicked(MailgunWebhookRequest $request): void
    {
        $email = $request->getRecipient();
        $ipAddress = $request->input('ip');
        $userAgent = $request->input('user-agent');
        $url = $request->input('url');

        $subscription = Subscription::where('email', $email)->first();

        if (!$subscription) {
            return;
        }

        $tracking = EmailTracking::where('subscription_id', $subscription->id)
            ->orderBy('sent_at', 'desc')
            ->first();

        if ($tracking) {
            $tracking->recordClick($ipAddress, $userAgent);

            // Increment campaign count if applicable
            if ($tracking->email_campaign_id) {
                $tracking->campaign?->incrementClicked();
            }
        }

        Log::info('Link clicked', [
            'email' => $email,
            'url' => $url,
        ]);
    }

    /**
     * Handle SendGrid webhook events.
     *
     * @param SendgridWebhookRequest $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/webhooks/mail/complaint",
     *     summary="Handle SendGrid webhook",
     *     tags={"Newsletter", "Webhooks"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="SendGrid webhook payload (array of events)"
     *     ),
     *     @OA\Response(response=200, description="Webhook processed"),
     *     @OA\Response(response=422, description="Invalid payload")
     * )
     */
    public function handleSendgridWebhook(SendgridWebhookRequest $request): JsonResponse
    {
        $events = $request->getEvents();

        Log::info('SendGrid webhook received', ['event_count' => count($events)]);

        foreach ($events as $event) {
            $this->processSendgridEvent($event);
        }

        return response()->json(['success' => true, 'message' => 'Webhook processed']);
    }

    /**
     * Process individual SendGrid event.
     */
    protected function processSendgridEvent(array $event): void
    {
        $eventType = $event['event'] ?? null;
        $email = $event['email'] ?? null;

        if (!$eventType || !$email) {
            return;
        }

        $subscription = Subscription::where('email', $email)->first();

        if (!$subscription) {
            return;
        }

        $tracking = EmailTracking::where('subscription_id', $subscription->id)
            ->orderBy('sent_at', 'desc')
            ->first();

        switch ($eventType) {
            case 'bounce':
                $this->processSendgridBounce($subscription, $tracking, $event);
                break;
            case 'spamreport':
                $this->processSendgridSpam($subscription, $tracking, $event);
                break;
            case 'unsubscribe':
                $this->processSendgridUnsubscribe($subscription, $event);
                break;
            case 'delivered':
                $tracking?->recordDelivery();
                break;
            case 'open':
                $this->processSendgridOpen($subscription, $tracking, $event);
                break;
            case 'click':
                $this->processSendgridClick($subscription, $tracking, $event);
                break;
        }
    }

    /**
     * Process SendGrid bounce event.
     */
    protected function processSendgridBounce(Subscription $subscription, ?EmailTracking $tracking, array $event): void
    {
        $bounceType = ($event['type'] ?? '') === 'blocked' 
            ? EmailTracking::BOUNCE_HARD 
            : EmailTracking::BOUNCE_SOFT;
        $reason = $event['reason'] ?? null;

        if ($tracking) {
            $this->newsletterService->recordBounce($tracking->id, $bounceType, $reason);
        } else {
            EmailTracking::create([
                'subscription_id' => $subscription->id,
                'email_type' => 'bounced',
                'bounced_at' => now(),
                'bounce_type' => $bounceType,
                'bounce_reason' => $reason,
            ]);

            if ($bounceType === EmailTracking::BOUNCE_HARD) {
                $subscription->update([
                    'is_active' => false,
                    'unsubscribed_at' => now(),
                ]);
            }
        }

        Log::info('SendGrid bounce recorded', [
            'email' => $subscription->email,
            'type' => $bounceType,
        ]);
    }

    /**
     * Process SendGrid spam complaint event.
     */
    protected function processSendgridSpam(Subscription $subscription, ?EmailTracking $tracking, array $event): void
    {
        if ($tracking) {
            $this->newsletterService->recordComplaint($tracking->id, EmailTracking::COMPLAINT_SPAM);
        } else {
            EmailTracking::create([
                'subscription_id' => $subscription->id,
                'email_type' => 'complained',
                'complained_at' => now(),
                'complaint_type' => EmailTracking::COMPLAINT_SPAM,
            ]);

            $subscription->update([
                'is_active' => false,
                'unsubscribed_at' => now(),
            ]);
        }

        Log::warning('SendGrid spam complaint', ['email' => $subscription->email]);
    }

    /**
     * Process SendGrid unsubscribe event.
     */
    protected function processSendgridUnsubscribe(Subscription $subscription, array $event): void
    {
        $subscription->update([
            'is_active' => false,
            'unsubscribed_at' => now(),
        ]);

        Log::info('SendGrid unsubscribe', ['email' => $subscription->email]);
    }

    /**
     * Process SendGrid open event.
     */
    protected function processSendgridOpen(Subscription $subscription, ?EmailTracking $tracking, array $event): void
    {
        if ($tracking) {
            $tracking->recordOpen($event['ip'] ?? null, $event['useragent'] ?? null);

            if ($tracking->email_campaign_id) {
                $tracking->campaign?->incrementOpened();
            }
        }
    }

    /**
     * Process SendGrid click event.
     */
    protected function processSendgridClick(Subscription $subscription, ?EmailTracking $tracking, array $event): void
    {
        if ($tracking) {
            $tracking->recordClick($event['ip'] ?? null, $event['useragent'] ?? null);

            if ($tracking->email_campaign_id) {
                $tracking->campaign?->incrementClicked();
            }
        }

        Log::info('SendGrid click', [
            'email' => $subscription->email,
            'url' => $event['url'] ?? null,
        ]);
    }

    /**
     * Generic bounce webhook endpoint (for other providers).
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function handleGenericBounce(\Illuminate\Http\Request $request): JsonResponse
    {
        $email = $request->input('email');
        $bounceType = $request->input('bounce_type', 'soft');
        $reason = $request->input('reason');

        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Email is required',
            ], 422);
        }

        $subscription = Subscription::where('email', $email)->first();

        if (!$subscription) {
            return response()->json(['success' => true, 'message' => 'Email not found']);
        }

        $tracking = EmailTracking::where('subscription_id', $subscription->id)
            ->orderBy('sent_at', 'desc')
            ->first();

        $type = $bounceType === 'hard' ? EmailTracking::BOUNCE_HARD : EmailTracking::BOUNCE_SOFT;

        if ($tracking) {
            $this->newsletterService->recordBounce($tracking->id, $type, $reason);
        } else {
            EmailTracking::create([
                'subscription_id' => $subscription->id,
                'email_type' => 'bounced',
                'bounced_at' => now(),
                'bounce_type' => $type,
                'bounce_reason' => $reason,
            ]);

            if ($type === EmailTracking::BOUNCE_HARD) {
                $subscription->update([
                    'is_active' => false,
                    'unsubscribed_at' => now(),
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Generic complaint webhook endpoint (for other providers).
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function handleGenericComplaint(\Illuminate\Http\Request $request): JsonResponse
    {
        $email = $request->input('email');

        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Email is required',
            ], 422);
        }

        $subscription = Subscription::where('email', $email)->first();

        if (!$subscription) {
            return response()->json(['success' => true, 'message' => 'Email not found']);
        }

        $tracking = EmailTracking::where('subscription_id', $subscription->id)
            ->orderBy('sent_at', 'desc')
            ->first();

        if ($tracking) {
            $this->newsletterService->recordComplaint($tracking->id, EmailTracking::COMPLAINT_SPAM);
        } else {
            EmailTracking::create([
                'subscription_id' => $subscription->id,
                'email_type' => 'complained',
                'complained_at' => now(),
                'complaint_type' => EmailTracking::COMPLAINT_SPAM,
            ]);

            $subscription->update([
                'is_active' => false,
                'unsubscribed_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }
}
