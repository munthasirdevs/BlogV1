{{-- Base notification email template --}}
@extends('emails.layouts.base')

@section('title', $notification->title ?? 'Notification')

@section('content')
    @if(isset($greeting))
        <h2>{{ $greeting }}</h2>
    @endif

    @if(isset($intro))
        <p>{{ $intro }}</p>
    @endif

    @if(isset($content))
        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
            {!! $content !!}
        </div>
    @endif

    @if(isset($actionUrl) && isset($actionText))
        <div style="text-align: center; margin: 25px 0;">
            <a href="{{ $actionUrl }}" class="button">{{ $actionText }}</a>
        </div>
    @endif

    @if(isset($closingText))
        <p style="margin-top: 25px; color: #666666;">{{ $closingText }}</p>
    @endif

    @if(isset($extraContent))
        <div class="divider"></div>
        {!! $extraContent !!}
    @endif
@endsection

@section('footer_content')
    <p style="margin: 0 0 10px 0; font-size: 12px; color: #999999;">
        You're receiving this email because you have notification preferences enabled for this type of notification.
    </p>
@endsection

@section('unsubscribe_link')
    <a href="{{ config('app.url') }}/settings/notifications" style="font-size: 12px; color: #999999;">
        Manage notification preferences
    </a>
@endsection
