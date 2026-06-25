@props(['simple' => false])

<footer style="background-color: var(--color-surface-header); border-top: 1px solid var(--color-border);">
    @if (!$simple)
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Brand -->
            <div class="col-span-1 md:col-span-2">
                <a href="{{ url('/') }}" class="text-lg font-bold" style="color: var(--color-text-heading);">XenonBlog</a>
                <p class="mt-2 text-sm max-w-md" style="color: var(--color-text-muted);">
                    A premium AI-powered blogging platform. Write, publish, and grow with XenonBlog.
                </p>
            </div>

            <!-- Quick Links -->
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider" style="color: var(--color-text-heading);">Platform</h3>
                <ul class="mt-4 space-y-2">
                    <li><a href="{{ route('blog.index') }}" class="text-sm hover:underline" style="color: var(--color-text-muted);">Blog</a></li>
                    <li><a href="{{ route('category.show', ['slug' => 'technology']) }}" class="text-sm hover:underline" style="color: var(--color-text-muted);">Categories</a></li>
                    <li><a href="{{ route('about') }}" class="text-sm hover:underline" style="color: var(--color-text-muted);">About</a></li>
                    <li><a href="{{ route('contact') }}" class="text-sm hover:underline" style="color: var(--color-text-muted);">Contact</a></li>
                </ul>
            </div>

            <!-- Legal -->
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider" style="color: var(--color-text-heading);">Legal</h3>
                <ul class="mt-4 space-y-2">
                    <li><a href="{{ route('privacy') }}" class="text-sm hover:underline" style="color: var(--color-text-muted);">Privacy</a></li>
                    <li><a href="{{ route('terms') }}" class="text-sm hover:underline" style="color: var(--color-text-muted);">Terms</a></li>
                </ul>
            </div>
        </div>
    </div>
    @endif

    <!-- Copyright -->
    <div class="border-t" style="border-color: var(--color-border);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <p class="text-sm" style="color: var(--color-text-muted);">&copy; {{ date('Y') }} XenonBlog. All rights reserved.</p>
            <p class="text-sm" style="color: var(--color-text-muted);">Powered by Laravel</p>
        </div>
    </div>
</footer>