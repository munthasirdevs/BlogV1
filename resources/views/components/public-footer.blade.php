@props(['categories' => []])

<footer class="relative" style="background-color: #0f172a;">
    {{-- Top gradient border --}}
    <div class="h-1 w-full" style="background: linear-gradient(90deg, var(--color-primary-600), var(--color-primary-400), #a78bfa);"></div>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        {{-- Main Grid --}}
        <div class="grid gap-10 py-16 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Brand --}}
            <div class="sm:col-span-2 lg:col-span-1">
                <a href="{{ route('blog.index') }}" class="flex items-center gap-1.5 mb-4">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-sm" style="background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-400));">X</span>
                    <span class="text-lg font-bold text-white">XenonBlog</span>
                </a>
                <p class="text-sm leading-relaxed" style="color: #94a3b8;">A premium AI-powered blogging platform. Write, publish, and grow your audience with powerful tools.</p>
                {{-- Social --}}
                <div class="flex items-center gap-3 mt-6">
                    <a href="#" class="w-9 h-9 rounded-lg flex items-center justify-center text-xs font-semibold transition-all duration-200 hover:scale-105" style="background-color: #1e293b; color: #94a3b8;">TW</a>
                    <a href="#" class="w-9 h-9 rounded-lg flex items-center justify-center text-xs font-semibold transition-all duration-200 hover:scale-105" style="background-color: #1e293b; color: #94a3b8;">FB</a>
                    <a href="#" class="w-9 h-9 rounded-lg flex items-center justify-center text-xs font-semibold transition-all duration-200 hover:scale-105" style="background-color: #1e293b; color: #94a3b8;">LN</a>
                    <a href="#" class="w-9 h-9 rounded-lg flex items-center justify-center text-xs font-semibold transition-all duration-200 hover:scale-105" style="background-color: #1e293b; color: #94a3b8;">GH</a>
                </div>
            </div>

            {{-- Quick Links --}}
            <div>
                <h4 class="text-sm font-semibold text-white mb-4">Platform</h4>
                <ul class="space-y-3">
                    <li><a href="{{ route('blog.index') }}" class="text-sm transition-colors duration-200 hover:text-white" style="color: #94a3b8;">Home</a></li>
                    <li><a href="{{ route('blog.index') }}" class="text-sm transition-colors duration-200 hover:text-white" style="color: #94a3b8;">Blog</a></li>
                    <li><a href="{{ route('about') }}" class="text-sm transition-colors duration-200 hover:text-white" style="color: #94a3b8;">About</a></li>
                    <li><a href="{{ route('contact') }}" class="text-sm transition-colors duration-200 hover:text-white" style="color: #94a3b8;">Contact</a></li>
                </ul>
            </div>

            {{-- Categories --}}
            <div>
                <h4 class="text-sm font-semibold text-white mb-4">Categories</h4>
                <ul class="space-y-3">
                    @foreach($categories->take(5) as $cat)
                    <li><a href="{{ route('category.show', $cat->slug) }}" class="text-sm transition-colors duration-200 hover:text-white" style="color: #94a3b8;">{{ $cat->name }}</a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Newsletter --}}
            <div>
                <h4 class="text-sm font-semibold text-white mb-4">Stay updated</h4>
                <p class="text-sm mb-4" style="color: #94a3b8;">Get the latest articles delivered to your inbox.</p>
                <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex flex-col gap-3">
                    @csrf
                    <input type="email" name="email" placeholder="Enter your email" required class="w-full px-4 py-2.5 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-500)]" style="background-color: #1e293b; border: 1px solid #334155; color: #f1f5f9;">
                    <button type="submit" class="w-full px-4 py-2.5 rounded-lg text-sm font-medium text-white transition-all duration-200 hover:opacity-90" style="background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-400));">Subscribe</button>
                </form>
            </div>
        </div>

        {{-- Bottom Bar --}}
        <div class="flex flex-col sm:flex-row items-center justify-between py-6 gap-4" style="border-top: 1px solid #1e293b;">
            <p class="text-xs" style="color: #64748b;">&copy; {{ date('Y') }} XenonBlog. All rights reserved.</p>
            <div class="flex items-center gap-6">
                <a href="{{ route('privacy') }}" class="text-xs transition-colors duration-200 hover:text-white" style="color: #64748b;">Privacy</a>
                <a href="{{ route('terms') }}" class="text-xs transition-colors duration-200 hover:text-white" style="color: #64748b;">Terms</a>
            </div>
        </div>
    </div>
</footer>
