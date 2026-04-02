{{-- Mention Notification Email --}}
@extends('emails.layouts.base')

@section('title', 'You were mentioned in a comment')

@section('content')
    <h2 style="color: #1a1a2e; margin-top: 0;">You Were Mentioned! 👋</h2>

    <p style="font-size: 16px; color: #666666;">
        Hi {{ $recipientName ?? 'there' }},
    </p>

    <p style="font-size: 16px;">
        <strong>{{ $mentionerName ?? 'Someone' }}</strong> mentioned you in a comment on
        <strong>{{ $postTitle ?? 'a post' }}</strong>.
    </p>

    <div style="background-color: #f8f9fa; padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #8b5cf6;">
        <div style="display: flex; align-items: center; margin-bottom: 15px;">
            @if(isset($mentionerAvatar))
                <img src="{{ $mentionerAvatar }}" alt="{{ $mentionerName }}" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 12px;">
            @endif
            <div>
                <p style="margin: 0; font-weight: 600; color: #1a1a2e;">{{ $mentionerName ?? 'Unknown' }}</p>
                <p style="margin: 0; font-size: 13px; color: #999999;">{{ $timeAgo ?? 'Just now' }}</p>
            </div>
        </div>

        <div style="background-color: #ffffff; padding: 15px; border-radius: 6px; border: 1px solid #e5e7eb;">
            <p style="margin: 0; color: #333333; line-height: 1.6;">
                {!! $mentionContext ?? 'You were mentioned in this comment.' !!}
            </p>
        </div>

        @if(isset($postTitle))
            <p style="margin: 15px 0 0 0; font-size: 14px; color: #666666;">
                📄 On post: <strong>{{ $postTitle }}</strong>
            </p>
        @endif
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $actionUrl ?? '#' }}" class="button" style="display: inline-block; background-color: #8b5cf6; color: #ffffff !important; padding: 14px 30px; text-decoration: none; border-radius: 6px; font-weight: 600;">
            View Comment →
        </a>
    </div>

    <p style="color: #666666; font-size: 14px; margin-top: 25px;">
        Join the conversation by replying to this comment!
    </p>

    <p style="color: #999999; font-size: 13px; margin-top: 25px;">
        You're receiving this because someone mentioned you using @{{ $recipientName ?? 'your username' }}.
    </p>
@endsection

@section('footer_content')
    <p style="margin: 0 0 10px 0; font-size: 12px; color: #999999;">
        Don't want to receive mention notifications?
    </p>
@endsection

@section('unsubscribe_link')
    <a href="{{ config('app.url') }}/settings/notifications" style="font-size: 12px; color: #999999;">
        Manage notification preferences
    </a>
@endsection
