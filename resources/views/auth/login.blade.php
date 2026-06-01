@extends('layouts.app')

@section('title', 'تسجيل دخول المدير')

@section('content')
    <section class="mx-auto max-w-md">
        <div class="page-hero mb-8 text-center">
            <p class="page-eyebrow">Admin</p>
            <h1 class="page-title">تسجيل دخول المدير</h1>
            <p class="page-subtitle">الزائر يتصفّح البطاقات ويطبع فقط. المدير يحصل على صلاحيات الإدارة الكاملة.</p>
        </div>

        @if (! $adminConfigured)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                لم يُضبط حساب المدير بعد. أضف <code class="rounded bg-amber-100 px-1">ADMIN_USERNAME</code> و
                <code class="rounded bg-amber-100 px-1">ADMIN_PASSWORD</code> في ملف <code class="rounded bg-amber-100 px-1">.env</code> على الخادم.
            </div>
        @else
            <form method="POST" action="{{ route('login.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf

                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <label for="username" class="mb-1 block text-sm font-medium text-slate-700">اسم المستخدم</label>
                        <input
                            id="username"
                            name="username"
                            type="text"
                            value="{{ old('username') }}"
                            autocomplete="username"
                            required
                            autofocus
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200/60"
                        />
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-sm font-medium text-slate-700">كلمة المرور</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200/60"
                        />
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between gap-3">
                    <a href="{{ route('languages.index') }}" class="btn btn-secondary">متابعة كزائر</a>
                    <button type="submit" class="btn btn-primary">دخول</button>
                </div>
            </form>
        @endif
    </section>
@endsection
