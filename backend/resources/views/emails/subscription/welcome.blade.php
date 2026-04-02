@extends('emails.layouts.base')

@section('title', 'Welcome to ' . config('app.name'))

@section('header', 'Welcome!')

@section('content')
    <h2>Welcome to {{ config('app.name') }}! 🎉</h2>
    
    <p>Your subscription has been confirmed. You're now part of our community!</p>
    
    <p>Here's what you can expect from us:</p>
    
    <table role="presentation" style="width: 100%; margin: 20px 0;">
        <tr>
            <td style="padding: 15px; background-color: #f8f9fa; border-radius: 6px; margin-bottom: 10px;">
                <strong>📰 Latest Articles</strong>
                <p style="margin: 5px 0 0 0; color: #666666; font-size: 14px;">
                    Get notified when we publish new articles and tutorials
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding: 15px; background-color: #f8f9fa; border-radius: 6px; margin-bottom: 10px;">
                <strong>📬 {{ ucfirst($frequency) }} Digest</strong>
                <p style="margin: 5px 0 0 0; color: #666666; font-size: 14px;">
                    Curated content delivered to your inbox {{ $frequency }}
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding: 15px; background-color: #f8f9fa; border-radius: 6px;">
                <strong>⚡ Exclusive Content</strong>
                <p style="margin: 5px 0 0 0; color: #666666; font-size: 14px;">
                    Access to subscriber-only resources and updates
                </p>
            </td>
        </tr>
    </table>
    
    <p style="text-align: center;">
        <a href="{{ $preferencesUrl }}" class="button" style="color: #ffffff;">Manage Preferences</a>
    </p>
    
    <div class="divider"></div>
    
    <h3>Popular Articles</h3>
    
    @if(isset($popularPosts) && count($popularPosts) > 0)
        @foreach($popularPosts as $post)
            <table role="presentation" style="width: 100%; margin-bottom: 15px;">
                <tr>
                    <td style="padding: 15px; border: 1px solid #e5e7eb; border-radius: 6px;">
                        <h4 style="margin: 0 0 8px 0; color: #1a1a2e;">
                            <a href="{{ $post['url'] }}" style="color: #4f46e5; text-decoration: none;">
                                {{ $post['title'] }}
                            </a>
                        </h4>
                        <p style="margin: 0; color: #666666; font-size: 14px;">
                            {{ Str::limit($post['excerpt'], 100) }}
                        </p>
                    </td>
                </tr>
            </table>
        @endforeach
    @else
        <p>Check out our latest articles on the blog!</p>
        <p style="text-align: center;">
            <a href="{{ config('app.url') }}/blog" style="color: #4f46e5; text-decoration: none;">
                Browse Articles →
            </a>
        </p>
    @endif
    
    <p style="margin-top: 25px;">
        Thanks for joining us!
        <br>
        <strong>The {{ config('app.name') }} Team</strong>
    </p>
@endsection

@section('footer_content')
    <p style="margin: 0 0 10px 0;">
        You're receiving this email as a confirmed subscriber of {{ config('app.name') }}.
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
