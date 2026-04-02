{{-- Notification Digest Email --}}
@extends('emails.layouts.base')

@section('title', 'Your Notification Digest - ' . ($period ?? 'Recent'))

@section('content')
    <h2 style="color: #1a1a2e; margin-top: 0;">Your {{ $period ?? 'Recent' }} Digest 📬</h2>

    <p style="font-size: 16px; color: #666666;">
        Hi {{ $userName ?? 'there' }},
    </p>

    <p style="font-size: 16px;">
        Here's what you missed on <strong>{{ config('app.name') }}</strong> 
        @if(isset($startDate) && isset($endDate))
            from {{ $startDate->format('M j') }} to {{ $endDate->format('M j, Y') }}
        @endif:
    </p>

    <div style="background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 15px; margin: 20px 0;">
        <p style="margin: 0; font-size: 14px; color: #0369a1;">
            <strong>{{ $totalCount ?? 0 }}</strong> notification(s) 
            @if(isset($unreadCount) && $unreadCount > 0)
                (<strong>{{ $unreadCount }}</strong> unread)
            @endif
        </p>
    </div>

    @if(isset($notifications) && count($notifications) > 0)
        @foreach($notifications as $notification)
            @php
                $data = $notification->data ?? [];
                $type = $data['type'] ?? 'other';
                $title = $data['title'] ?? 'Notification';
                $message = $data['message'] ?? '';
                $actionUrl = $data['action_url'] ?? '#';
                $fromUser = $data['from_user'] ?? null;
                $createdAt = $notification->created_at ?? null;
                $isRead = $notification->read_at !== null;
            @endphp

            <div style="background-color: {{ $isRead ? '#f9fafb' : '#ffffff' }}; border: 1px solid {{ $isRead ? '#e5e7eb' : '#e0e7ff' }}; border-radius: 8px; padding: 20px; margin: 15px 0; {{ !$isRead ? 'border-left: 4px solid #4f46e5;' : '' }}">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                    <h3 style="margin: 0; color: #1a1a2e; font-size: 16px;">{{ $title }}</h3>
                    @if(!$isRead)
                        <span style="background-color: #4f46e5; color: #ffffff; font-size: 11px; padding: 3px 8px; border-radius: 10px; font-weight: 600;">NEW</span>
                    @endif
                </div>

                @if($fromUser)
                    <div style="display: flex; align-items: center; margin-bottom: 10px;">
                        @if(isset($fromUser['avatar']) && $fromUser['avatar'])
                            <img src="{{ $fromUser['avatar'] }}" alt="{{ $fromUser['name'] }}" style="width: 24px; height: 24px; border-radius: 50%; margin-right: 8px;">
                        @endif
                        <span style="font-size: 13px; color: #666666;">
                            From: <strong>{{ $fromUser['name'] ?? 'Unknown' }}</strong>
                        </span>
                    </div>
                @endif

                @if($message)
                    <p style="margin: 0 0 15px 0; color: #444444; line-height: 1.5; font-size: 14px;">
                        {{ \Illuminate\Support\Str::limit($message, 150) }}
                    </p>
                @endif

                @if($createdAt)
                    <p style="margin: 0 0 15px 0; font-size: 12px; color: #999999;">
                        {{ $createdAt->diffForHumans() }}
                    </p>
                @endif

                <a href="{{ $actionUrl }}" style="display: inline-block; background-color: #4f46e5; color: #ffffff !important; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: 500; font-size: 14px;">
                    View →
                </a>
            </div>
        @endforeach
    @else
        <div style="text-align: center; padding: 40px; color: #999999;">
            <p style="font-size: 16px;">No notifications for this period.</p>
        </div>
    @endif

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ config('app.url') }}/notifications" class="button" style="display: inline-block; background-color: #1a1a2e; color: #ffffff !important; padding: 14px 30px; text-decoration: none; border-radius: 6px; font-weight: 600;">
            View All Notifications
        </a>
    </div>

    <div class="divider"></div>

    <p style="color: #666666; font-size: 14px; text-align: center;">
        Want to change how often you receive these digests?
    </p>
    <p style="text-align: center;">
        <a href="{{ config('app.url') }}/settings/notifications" style="color: #4f46e5; text-decoration: none; font-size: 14px;">
            Update notification preferences →
        </a>
    </p>
@endsection

@section('footer_content')
    <p style="margin: 0 0 10px 0; font-size: 12px; color: #999999;">
        You're receiving this digest because you have daily/weekly digest enabled.
    </p>
@endsection

@section('unsubscribe_link')
    <a href="{{ config('app.url') }}/settings/notifications" style="font-size: 12px; color: #999999;">
        Manage digest preferences
    </a>
@endsection
