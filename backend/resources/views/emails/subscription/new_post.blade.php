@extends('emails.layouts.base')

@section('title', 'New Post: ' . ($post['title'] ?? 'Article'))

@section('header', 'New Article Published')

@section('content')
    <p>Hi there!</p>
    
    <p>A new article has been published on {{ config('app.name') }} that matches your interests.</p>
    
    <div style="background-color: #f8f9fa; padding: 25px; border-radius: 8px; border-left: 4px solid #4f46e5; margin: 25px 0;">
        @if(isset($post['category']))
            <span style="color: #4f46e5; font-size: 13px; font-weight: 600; text-transform: uppercase;">
                {{ $post['category'] }}
            </span>
        @endif
        
        <h2 style="margin: 10px 0; color: #1a1a2e;">
            <a href="{{ $post['url'] }}" style="color: #1a1a2e; text-decoration: none;">
                {{ $post['title'] }}
            </a>
        </h2>
        
        @if(isset($post['author']))
            <p style="margin: 0 0 15px 0; color: #666666; font-size: 14px;">
                By <strong>{{ $post['author'] }}</strong>
                @if(isset($post['publishedAt']))
                    · {{ $post['publishedAt'] }}
                @endif
            </p>
        @endif
        
        <p style="margin: 0 0 20px 0; color: #555555; line-height: 1.6;">
            {{ Str::limit($post['excerpt'] ?? $post['content'], 200) }}
        </p>
        
        <p>
            <a href="{{ $post['url'] }}" class="button" style="color: #ffffff;">Read Article</a>
        </p>
    </div>
    
    @if(isset($tags) && count($tags) > 0)
        <p style="margin-bottom: 10px;"><strong>Tags:</strong></p>
        <p>
            @foreach($tags as $tag)
                <span style="display: inline-block; background-color: #eef2ff; color: #4f46e5; padding: 4px 12px; border-radius: 20px; font-size: 13px; margin-right: 8px; margin-bottom: 8px;">
                    #{{ $tag }}
                </span>
            @endforeach
        </p>
    @endif
    
    <div class="divider"></div>
    
    <h3>More from {{ config('app.name') }}</h3>
    
    @if(isset($relatedPosts) && count($relatedPosts) > 0)
        @foreach(array_slice($relatedPosts, 0, 3) as $relatedPost)
            <table role="presentation" style="width: 100%; margin-bottom: 10px;">
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">
                        <a href="{{ $relatedPost['url'] }}" style="color: #4f46e5; text-decoration: none; font-weight: 500;">
                            {{ $relatedPost['title'] }}
                        </a>
                    </td>
                </tr>
            </table>
        @endforeach
    @endif
    
    <p style="text-align: center; margin-top: 20px;">
        <a href="{{ config('app.url') }}/blog" style="color: #4f46e5; text-decoration: none;">
            View all articles →
        </a>
    </p>
@endsection

@section('footer_content')
    <p style="margin: 0 0 10px 0;">
        You received this email because you subscribed to new post notifications.
    </p>
    
    <p style="margin: 0;">
        Email: {{ $email }}
    </p>
@endsection

@section('unsubscribe_link')
    <a href="{{ $unsubscribeUrl }}" style="color: #888888; font-size: 12px;">Unsubscribe</a>
    <span style="color: #cccccc;">|</span>
    <a href="{{ $preferencesUrl }}" style="color: #888888; font-size: 12px;">Manage Preferences</a>
@endsection

@section('tracking_pixel')
    <img src="{{ $trackingUrl }}" width="1" height="1" alt="" style="display: none;" />
@endsection
