@extends('layouts.app')

@section('title', 'خيارات الطباعة - ' . $deck->name)

@section('content')
    <div class="mb-4 text-sm text-slate-500">
        <a href="{{ route('languages.index') }}" class="text-indigo-600 hover:underline">اللغات</a>
        <span class="mx-1">/</span>
        @if ($deck->level?->language)
            <a href="{{ route('languages.show', $deck->level->language) }}" class="text-indigo-600 hover:underline">{{ $deck->level->language->name }}</a>
            <span class="mx-1">/</span>
        @endif
        @if ($deck->level)
            <a href="{{ route('levels.show', $deck->level) }}" class="text-indigo-600 hover:underline">{{ $deck->level->name }}</a>
            <span class="mx-1">/</span>
        @endif
        <a href="{{ route('decks.show', $deck) }}" class="text-indigo-600 hover:underline">{{ $deck->name }}</a>
        <span class="mx-1">/</span>
        <span>خيارات الطباعة</span>
    </div>

    <section class="page-hero mb-8">
        <div class="flex items-start gap-3">
            <span class="inline-flex h-14 w-14 rounded-2xl shrink-0 shadow-inner" style="background-color: {{ $deck->color }}"></span>
            <div>
                <p class="page-eyebrow">طباعة</p>
                <h1 class="page-title">{{ $deck->name }}</h1>
                <p class="page-subtitle max-w-xl">اختر طريقة الطباعة المناسبة لك.</p>
            </div>
        </div>
    </section>

    <section class="grid gap-6 md:grid-cols-2">
        <a href="{{ route('decks.print', $deck) }}"
           target="_blank"
           class="group rounded-2xl border-2 border-slate-200 bg-white p-6 shadow-sm transition hover:border-indigo-400 hover:shadow-lg">
            <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z" />
                </svg>
            </div>
            <h2 class="mb-2 text-lg font-bold text-slate-900">الإعدادات الافتراضية</h2>
            <p class="text-sm text-slate-500">
                طباعة فورية بالإعدادات المعتمدة في الصفحة الحالية: ورقة A4 بشبكة 3×3،
                مع الحدود والحاشيات والمسافات الافتراضية بين البطاقات.
            </p>
            <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 group-hover:underline">
                طباعة افتراضية ←
            </span>
        </a>

        <a href="{{ route('decks.print', ['deck' => $deck, 'mode' => 'custom']) }}"
           target="_blank"
           class="group rounded-2xl border-2 border-slate-200 bg-white p-6 shadow-sm transition hover:border-indigo-400 hover:shadow-lg">
            <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-violet-50 text-violet-600 group-hover:bg-violet-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <h2 class="mb-2 text-lg font-bold text-slate-900">تخصيص الطباعة</h2>
            <p class="text-sm text-slate-500">
                تحكم كامل في حجم الورقة، عدد الصفوف والأعمدة، حدود البطاقة،
                هوامش الصفحة والمسافات بين البطاقات. يستخدم لمرة واحدة فقط.
            </p>
            <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-violet-600 group-hover:underline">
                فتح التخصيص ←
            </span>
        </a>
    </section>

    <div class="mt-8">
        <a href="{{ route('decks.show', $deck) }}" class="btn btn-secondary">رجوع</a>
    </div>
@endsection
