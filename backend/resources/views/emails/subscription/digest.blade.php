@extends('emails.layouts.base')

@section('title', $subject ?? config('app.name') . ' Digest')

@section('header', $subject ?? config('app.name') . ' Digest')

@section('content')
    <p>Hello!</p>
    
    <p>Here's your {{ $period }} digest from {{ config('app.name') }}. Catch up on the latest articles and updates you might have missed.</p>
    
    <div class="divider"></div>
    
    @if(isset($posts) && count($posts) > 0)
        @foreach($posts as $index => $post)
            <table role="presentation" style="width: 100%; margin-bottom: 20px;">
                <tr>
                    <td style="padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px;">
                        @if($index === 0)
                            <span style="display: inline-block; background-color: #4f46e5; color: #ffffff; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; margin-bottom: 10px;">
                                Featured
                            </span>
                        @endif
                        
                        <h3 style="margin: 0 0 10px 0; color: #1a1a2e;">
                            <a href="{{ $post['url'] }}" style="color: #1a1a2e; text-decoration: none;">
                                {{ $post['title'] }}
                            </a>
                        </h3>
                        
                        @if(isset($post['category']))
                            <p style="margin: 0 0 10px 0;">
                                <span style="color: #4f46e5; font-size: 13px; font-weight: 500;">
                                    {{ $post['category'] }}
                                </span>
                            </p>
                        @endif
                        
                        <p style="margin: 0 0 15px 0; color: #555555; line-height: 1.6;">
                            {{ Str::limit($post['excerpt'] ?? $post['content'], 150) }}
                        </p>
                        
                        <p style="margin: 0;">
                            <a href="{{ $post['url'] }}" style="color: #4f46e5; text-decoration: none; font-weight: 500;">
                                Read more →
                            </a>
                        </p>
                    </td>
                </tr>
            </table>
        @endforeach
    @else
        <p style="text-align: center; padding: 40px 0; color: #888888;">
            No new articles this period. Check back soon!
        </p>
    @endif
    
    <div class="divider"></div>
    
    <h3>Quick Links</h3>
    
    <table role="presentation" style="width: 100%;">
        <tr>
            <td style="padding: 10px;">
                <a href="{{ config('app.url') }}/blog" style="color: #4f46e5; text-decoration: none;">
                    📚 Browse All Articles
                </a>
            </td>
            <td style="padding: 10px;">
                <a href="{{ config('app.url') }}/categories" style="color: #4f46e5; text-decoration: none;">
                    📁 Categories
                </a>
            </td>
            <td style="padding: 10px;">
                <a href="{{ config('app.url') }}/tags" style="color: #4f46e5; text-decoration: none;">
                    🏷️ Tags
                </a>
            </td>
        </tr>
    </table>
    
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 25px;">
        <h4 style="margin: 0 0 10px 0; color: #1a1a2e;">📊 Your Subscription</h4>
        <p style="margin: 0; color: #666666; font-size: 14px;">
            You're receiving this {{ $period }} digest because you subscribed with <strong>{{ $email }}</strong>.
        </p>
        <p style="margin: 10px 0 0 0; font-size: 14px;">
            <a href="{{ $preferencesUrl }}" style="color: #4f46e5; text-decoration: none;">Update preferences</a>
            <span style="color: #cccccc;">|</span>
            <a href="{{ $unsubscribeUrl }}" style="color: #888888; text-decoration: none;">Unsubscribe</a>
        </p>
    </div>
@endsection

@section('footer_content')
    <p style="margin: 0 0 10px 0;">
        This digest covers the period from {{ $startDate }} to {{ $endDate }}.
    </p>
    
    <p style="margin: 0;">
        Don't want to receive these emails? 
        <a href="{{ $unsubscribeUrl }}" style="color: #888888;">Unsubscribe here</a>.
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
