@extends('layouts.app')

@section('title', 'تأكيد الاستيراد - ' . $language->name)

@section('content')
    <div class="mb-4 text-sm text-slate-500">
        <a href="{{ route('languages.index') }}" class="text-indigo-600 hover:underline">اللغات</a>
        <span class="mx-1">/</span>
        <a href="{{ route('languages.show', $language) }}" class="text-indigo-600 hover:underline">{{ $language->name }}</a>
        <span class="mx-1">/</span>
        <a href="{{ route('languages.import.form', $language) }}" class="text-indigo-600 hover:underline">استيراد</a>
        <span class="mx-1">/</span>
        <span>تأكيد المكررات</span>
    </div>

    <section class="page-hero mb-8">
        <div>
            <p class="page-eyebrow">تأكيد الاستيراد</p>
            <h1 class="page-title">{{ $language->name }}</h1>
        </div>
    </section>

    <section class="mb-8 grid grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">عدد الكلمات الموجودة</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $analysis['total'] }}</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <p class="text-sm text-amber-700">عدد الكلمات المكررة</p>
            <p class="mt-2 text-3xl font-bold text-amber-800">{{ $analysis['duplicate_rows'] }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm text-emerald-700">عدد الكلمات التي ستُدرج</p>
            <p class="mt-2 text-3xl font-bold text-emerald-800">{{ $analysis['will_import'] }}</p>
        </div>
    </section>

    <section class="mb-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-bold text-slate-900">الكلمات المكررة</h2>
        <p class="mb-4 text-sm text-slate-600">
            سيتم الإبقاء على أول ظهور لكل كلمة وحذف التكرارات التالية بعد المتابعة.
        </p>
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-right font-semibold">الكلمة</th>
                        <th class="px-4 py-3 text-right font-semibold">أرقام الصفوف</th>
                        <th class="px-4 py-3 text-right font-semibold">مرات التكرار</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($analysis['duplicates'] as $item)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $item['word'] }}</td>
                            <td class="px-4 py-3 text-slate-700" dir="ltr">{{ implode(', ', $item['lines']) }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ count($item['lines']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="flex flex-wrap items-center gap-3">
        <form action="{{ route('languages.import.confirm.submit', $language) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary">متابعة الاستيراد</button>
        </form>
        <form action="{{ route('languages.import.cancel', $language) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-secondary">إلغاء</button>
        </form>
    </div>
@endsection
