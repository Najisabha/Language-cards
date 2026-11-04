<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'بطاقات تعلم الإنجليزية') | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700|inter:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-100 via-slate-50 to-white text-slate-800 antialiased">
    <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-md">
        <div class="mx-auto max-w-6xl px-4 py-4 flex items-center justify-between">
            <a href="{{ route('languages.index') }}" class="flex items-center gap-2">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white font-bold shadow-lg shadow-indigo-200">F</span>
                <span class="font-extrabold text-lg tracking-tight">Flashcards</span>
            </a>

            <nav class="flex items-center gap-1.5 sm:gap-2">
                {{-- اللغات --}}
                <a href="{{ route('languages.index') }}"
                   title="اللغات"
                   aria-label="اللغات"
                   class="inline-flex h-10 w-10 items-center justify-center rounded-full shadow-sm transition hover:opacity-90 {{ request()->routeIs('languages.*') && ! request()->routeIs('languages.create') ? 'ring-2 ring-offset-2 ring-sky-300' : '' }}"
                   style="background-color:#0ea5e9;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" class="h-5 w-5" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M3 12h18M12 3c3 3.5 3 14.5 0 18M12 3c-3 3.5-3 14.5 0 18" stroke-linecap="round"></path>
                    </svg>
                </a>

                {{-- المستويات --}}
                <a href="{{ route('levels.index') }}"
                   title="المستويات"
                   aria-label="المستويات"
                   class="inline-flex h-10 w-10 items-center justify-center rounded-full shadow-sm transition hover:opacity-90 {{ request()->routeIs('levels.*') ? 'ring-2 ring-offset-2 ring-emerald-300' : '' }}"
                   style="background-color:#10b981;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" class="h-5 w-5" aria-hidden="true">
                        <path d="M4 20h4V10H4v10zm6 0h4V4h-4v16zm6 0h4v-7h-4v7z" stroke-linejoin="round"></path>
                    </svg>
                </a>

                {{-- المجموعات --}}
                <a href="{{ route('decks.index') }}"
                   title="المجموعات"
                   aria-label="المجموعات"
                   class="inline-flex h-10 w-10 items-center justify-center rounded-full shadow-sm transition hover:opacity-90 {{ request()->routeIs('decks.*') ? 'ring-2 ring-offset-2 ring-violet-300' : '' }}"
                   style="background-color:#8b5cf6;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" class="h-5 w-5" aria-hidden="true">
                        <rect x="3" y="4" width="7" height="7" rx="1.5"></rect>
                        <rect x="14" y="4" width="7" height="7" rx="1.5"></rect>
                        <rect x="3" y="13" width="7" height="7" rx="1.5"></rect>
                        <rect x="14" y="13" width="7" height="7" rx="1.5"></rect>
                    </svg>
                </a>

                @admin
                    {{-- مكتبة الصور --}}
                    <a href="{{ route('media-images.index') }}"
                       title="مكتبة الصور"
                       aria-label="مكتبة الصور"
                       class="inline-flex h-10 w-10 items-center justify-center rounded-full shadow-sm transition hover:opacity-90 {{ request()->routeIs('media-images.*') || request()->routeIs('media-folders.*') ? 'ring-2 ring-offset-2 ring-fuchsia-300' : '' }}"
                       style="background-color:#d946ef;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" class="h-5 w-5" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                            <circle cx="8.5" cy="10" r="1.5"></circle>
                            <path d="M21 15l-5-5-8 8" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </a>

                    {{-- لغة جديدة --}}
                    <a href="{{ route('languages.create') }}"
                       title="لغة جديدة"
                       aria-label="لغة جديدة"
                       class="inline-flex h-10 w-10 items-center justify-center rounded-full shadow-sm transition hover:opacity-90 {{ request()->routeIs('languages.create') ? 'ring-2 ring-offset-2 ring-amber-300' : '' }}"
                       style="background-color:#f59e0b;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" class="h-5 w-5" aria-hidden="true">
                            <path d="M12 5v14M5 12h14" stroke-linecap="round"></path>
                        </svg>
                    </a>

                    {{-- خروج --}}
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit"
                                title="خروج"
                                aria-label="خروج"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full shadow-sm transition hover:opacity-90"
                                style="background-color:#ef4444;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" class="h-5 w-5" aria-hidden="true">
                                <path d="M10 7V5a2 2 0 0 1 2-2h7v18h-7a2 2 0 0 1-2-2v-2" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M15 12H3m0 0 3-3m-3 3 3 3" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </button>
                    </form>
                @else
                    {{-- دخول المدير --}}
                    <a href="{{ route('login') }}"
                       title="دخول المدير"
                       aria-label="دخول المدير"
                       class="inline-flex h-10 w-10 items-center justify-center rounded-full shadow-sm transition hover:opacity-90"
                       style="background-color:#64748b;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" class="h-5 w-5" aria-hidden="true">
                            <path d="M14 7V5a2 2 0 0 0-2-2H5v18h7a2 2 0 0 0 2-2v-2" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M9 12h12m0 0-3-3m3 3-3 3" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </a>
                @endadmin
            </nav>
        </div>
    </header>

    @if (session('status'))
        <div class="mx-auto max-w-6xl px-4 mt-4">
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm shadow-sm">{{ session('status') }}</div>
        </div>
    @endif

    <div class="mx-auto max-w-6xl px-4 pt-4 text-xs text-slate-500">
        عند التصفح: اللغة ← المستوى ← النوع (تحيات، أرقام…) ← كلمات أو جمل ← البطاقات.
    </div>

    <main class="mx-auto max-w-6xl px-4 py-8">@yield('content')</main>

    <footer class="mx-auto max-w-6xl px-4 py-6 text-center text-xs text-slate-400">Flashcards &middot; {{ date('Y') }}</footer>
</body>
</html>
