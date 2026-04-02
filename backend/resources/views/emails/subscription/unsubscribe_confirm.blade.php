@extends('emails.layouts.base')

@section('title', 'Unsubscribe Confirmed')

@section('header', 'Unsubscribe Confirmed')

@section('content')
    <div style="text-align: center; padding: 20px 0;">
        <div style="display: inline-block; background-color: #fee2e2; border-radius: 50%; padding: 15px; margin-bottom: 20px;">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </div>
    </div>
    
    <h2 style="text-align: center;">You've been unsubscribed</h2>
    
    <p>We're sorry to see you go. Your email <strong>{{ $email }}</strong> has been successfully unsubscribed from the {{ config('app.name') }} newsletter.</p>
    
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
        <h3 style="margin: 0 0 15px 0; color: #1a1a2e; font-size: 16px;">What does this mean?</h3>
        <ul style="margin: 0; padding-left: 20px; color: #555555; line-height: 1.8;">
            <li>You will no longer receive our newsletter</li>
            <li>You will no longer receive new post notifications</li>
            <li>You will no longer receive digest emails</li>
        </ul>
    </div>
    
    <div class="divider"></div>
    
    <h3>Changed your mind?</h3>
    
    <p>No worries! You can resubscribe at any time by clicking the button below:</p>
    
    <p style="text-align: center;">
        <a href="{{ $resubscribeUrl }}" class="button" style="color: #ffffff;">Resubscribe</a>
    </p>
    
    <p style="text-align: center; margin-top: 20px;">
        Or visit <a href="{{ config('app.url') }}/subscribe" style="color: #4f46e5; text-decoration: none;">{{ config('app.url') }}/subscribe</a>
    </p>
    
    <div class="divider"></div>
    
    <h3>We'd love your feedback</h3>
    
    <p style="color: #666666;">
        If you have a moment, we'd appreciate knowing why you unsubscribed. Your feedback helps us improve our content and emails.
    </p>
    
    <p>
        <a href="{{ config('app.url') }}/feedback?type=unsubscribe" style="color: #4f46e5; text-decoration: none;">
            Share feedback →
        </a>
    </p>
    
    <p style="margin-top: 30px; color: #888888; font-size: 14px;">
        Thank you for being a subscriber. We hope to see you again!
        <br>
        <strong>The {{ config('app.name') }} Team</strong>
    </p>
@endsection

@section('footer_content')
    <p style="margin: 0;">
        This email confirms your unsubscription from {{ config('app.name') }}.
    </p>
@endsection

@section('unsubscribe_link')
    <span style="color: #cccccc; font-size: 12px;">You are no longer subscribed</span>
@endsection

@section('tracking_pixel')
    <img src="{{ $trackingUrl }}" width="1" height="1" alt="" style="display: none;" />
@endsection
