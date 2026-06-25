<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:newsletter_subscribers,email'],
        ]);

        $validated['verification_token'] = Str::random(64);
        $validated['subscribed_at'] = now();

        $subscriber = NewsletterSubscriber::create($validated);

        Mail::send('emails.newsletter.verification', [
            'token' => $subscriber->verification_token,
            'email' => $subscriber->email,
        ], function ($message) use ($subscriber) {
            $message->to($subscriber->email)
                ->subject('Verify your newsletter subscription');
        });

        return redirect()->back()->with('success', 'Please check your email to verify your subscription.');
    }

    public function verify(string $token): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::where('verification_token', $token)
            ->whereNull('verified_at')
            ->firstOrFail();

        $subscriber->update([
            'verified_at' => now(),
            'verification_token' => null,
        ]);

        return redirect()->route('home')
            ->with('success', 'Your email has been verified. Thank you for subscribing!');
    }

    public function unsubscribe(string $email): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::where('email', $email)
            ->whereNotNull('verified_at')
            ->firstOrFail();

        $subscriber->update([
            'unsubscribed_at' => now(),
        ]);

        return redirect()->route('home')
            ->with('success', 'You have been unsubscribed successfully.');
    }
}
