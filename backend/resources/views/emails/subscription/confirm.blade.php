@extends('emails.layouts.base')

@section('title', 'Confirm Your Subscription')

@section('header', 'Confirm Your Subscription')

@section('content')
    <h2>Welcome to {{ config('app.name') }}!</h2>
    
    <p>Thank you for subscribing to our newsletter. To complete your subscription and start receiving updates, please confirm your email address by clicking the button below:</p>
    
    <p style="text-align: center;">
        <a href="{{ $confirmationUrl }}" class="button" style="color: #ffffff;">Confirm Subscription</a>
    </p>
    
    <p>Or copy and paste this link into your browser:</p>
    <p style="word-break: break-all; color: #4f46e5;">
        <a href="{{ $confirmationUrl }}" style="color: #4f46e5;">{{ $confirmationUrl }}</a>
    </p>
    
    <div class="divider"></div>
    
    <p><strong>What happens next?</strong></p>
    <ul style="color: #555555;">
        <li>Once confirmed, you'll start receiving our newsletter</li>
        <li>You can update your preferences at any time</li>
        <li>We respect your privacy and never share your email</li>
    </ul>
    
    <p style="color: #888888; font-size: 14px; margin-top: 25px;">
        This confirmation link will expire in 24 hours. If it expires, you can request a new confirmation email from our website.
    </p>
    
    <p style="color: #888888; font-size: 14px;">
        If you didn't sign up for this newsletter, you can safely ignore this email.
    </p>
@endsection

@section('footer_content')
    <p style="margin: 0 0 10px 0;">
        You received this email because you signed up for the {{ config('app.name') }} newsletter.
    </p>
@endsection

@section('unsubscribe_link')
    <a href="{{ $unsubscribeUrl }}" style="color: #888888; font-size: 12px;">Unsubscribe</a>
@endsection

@section('tracking_pixel')
    <img src="{{ $trackingUrl }}" width="1" height="1" alt="" style="display: none;" />
@endsection
