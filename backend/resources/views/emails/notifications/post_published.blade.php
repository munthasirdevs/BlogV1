{{-- Post Published Notification Email --}}
@extends('emails.layouts.base')

@section('title', 'New Post Published - ' . ($post->title ?? ''))

@section('content')
    <h2 style="color: #1a1a2e; margin-top: 0;">New Post Published!</h2>

    <p style="font-size: 16px; color: #666666;">
        Hi {{ $recipientName ?? 'there' }},
    </p>

    <p style="font-size: 16px;">
        A new post has been published on <strong>{{ config('app.name') }}</strong>!
    </p>

    <div style="background-color: #f8f9fa; padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #4f46e5;">
        <h3 style="margin: 0 0 10px 0; color: #1a1a2e; font-size: 18px;">
            {{ $post->title ?? 'New Post' }}
        </h3>

        @if(isset($authorName))
            <p style="margin: 0 0 15px 0; color: #666666; font-size: 14px;">
                By <strong>{{ $authorName }}</strong>
                @if(isset($authorAvatar))
                    <img src="{{ $authorAvatar }}" alt="{{ $authorName }}" style="width: 24px; height: 24px; border-radius: 50%; vertical-align: middle; margin-left: 8px;">
                @endif
            </p>
        @endif

        @if(isset($postExcerpt))
            <p style="margin: 0 0 15px 0; color: #444444; line-height: 1.6;">
                {{ $postExcerpt }}
            </p>
        @endif

        @if(isset($category))
            <p style="margin: 0 0 5px 0; font-size: 13px;">
                <span style="background-color: #e0e7ff; color: #4f46e5; padding: 4px 10px; border-radius: 4px;">
                    {{ $category }}
                </span>
            </p>
        @endif

        @if(isset($readingTime))
            <p style="margin: 0; font-size: 13px; color: #999999;">
                ⏱️ {{ $readingTime }} min read
            </p>
        @endif
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $actionUrl ?? '#' }}" class="button" style="display: inline-block; background-color: #4f46e5; color: #ffffff !important; padding: 14px 30px; text-decoration: none; border-radius: 6px; font-weight: 600;">
            Read Post →
        </a>
    </div>

    @if(isset($tags) && count($tags) > 0)
        <div class="divider"></div>
        <p style="font-size: 14px; color: #666666; margin-bottom: 10px;">Tags:</p>
        <div>
            @foreach($tags as $tag)
                <span style="display: inline-block; background-color: #f3f4f6; color: #666666; padding: 4px 12px; border-radius: 20px; font-size: 12px; margin: 0 5px 5px 0;">
                    #{{ $tag }}
                </span>
            @endforeach
        </div>
    @endif

    <p style="margin-top: 30px; color: #666666; font-size: 14px;">
        Happy reading! 📚
    </p>

    <p style="color: #999999; font-size: 13px; margin-top: 25px;">
        You're receiving this because you're subscribed to our newsletter.
    </p>
@endsection

@section('footer_content')
    <p style="margin: 0 0 10px 0; font-size: 12px; color: #999999;">
        Don't want to receive these emails?
    </p>
@endsection

@section('unsubscribe_link')
    <a href="{{ config('app.url') }}/settings/notifications" style="font-size: 12px; color: #999999;">
        Unsubscribe from newsletter
    </a>
    <span style="color: #999999;"> | </span>
    <a href="{{ config('app.url') }}/settings/notifications" style="font-size: 12px; color: #999999;">
        Manage preferences
    </a>
@endsection
