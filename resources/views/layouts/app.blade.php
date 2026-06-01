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

            <nav class="flex items-center gap-2 text-sm">
                <a href="{{ route('languages.index') }}"
                   class="px-3 py-2 rounded-md hover:bg-slate-100 {{ request()->routeIs('languages.*') ? 'bg-slate-100 font-semibold' : '' }}">اللغات</a>
                <a href="{{ route('levels.index') }}"
                   class="px-3 py-2 rounded-md hover:bg-slate-100 {{ request()->routeIs('levels.*') ? 'bg-slate-100 font-semibold' : '' }}">المستويات</a>
                <a href="{{ route('decks.index') }}"
                   class="px-3 py-2 rounded-md hover:bg-slate-100 {{ request()->routeIs('decks.*') ? 'bg-slate-100 font-semibold' : '' }}">المجموعات</a>
                @admin
                    <a href="{{ route('languages.create') }}" class="px-3 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 font-medium shadow-sm">+ لغة جديدة</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-2 rounded-md text-slate-600 hover:bg-slate-100">خروج</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="px-3 py-2 rounded-md text-slate-600 hover:bg-slate-100">دخول المدير</a>
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
