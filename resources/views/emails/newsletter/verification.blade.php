<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Newsletter Subscription</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #374151; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { display: inline-block; padding: 12px 24px; background-color: #6366f1; color: #ffffff; text-decoration: none; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verify Your Subscription</h2>
        <p>Thank you for subscribing to our newsletter!</p>
        <p>Please click the button below to verify your email address:</p>
        <p>
            <a href="{{ route('newsletter.verify', $token) }}" class="button">Verify Subscription</a>
        </p>
        <p>Or copy and paste this link into your browser:</p>
        <p>{{ route('newsletter.verify', $token) }}</p>
        <p>If you did not subscribe, please ignore this email.</p>
    </div>
</body>
</html>
