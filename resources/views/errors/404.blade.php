<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - {{ __('Page Not Found') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased flex items-center justify-center min-h-screen" style="background-color: var(--color-surface);">
    <div class="text-center max-w-md px-6">
        <div class="text-8xl font-bold mb-4" style="color: var(--color-primary-600);">404</div>
        <h1 class="text-2xl font-bold mb-2" style="color: var(--color-text-heading);">{{ __('Page Not Found') }}</h1>
        <p class="mb-8" style="color: var(--color-text-muted);">{{ __('The page you are looking for does not exist or has been moved.') }}</p>
        <a href="{{ route('home') }}" class="inline-flex items-center px-6 py-3 rounded-xl text-sm font-semibold text-white transition-all" style="background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-400));">
            {{ __('Back to Home') }}
        </a>
    </div>
</body>
</html>
