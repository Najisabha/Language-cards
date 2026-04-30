@extends('layouts.app')

@section('title', 'مجموعة جديدة')

@section('content')
    <div class="max-w-xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">إنشاء مجموعة جديدة</h1>
        <p class="mb-4 text-sm text-slate-500">
            أنت تضيف مجموعة داخل المستوى:
            <span class="font-semibold text-slate-900">{{ $selectedLevel->name }}</span>
            @if ($selectedLevel->language)
                <span>ضمن اللغة {{ $selectedLevel->language->name }}</span>
            @endif
        </p>

        <form method="POST" action="{{ route('decks.store') }}" class="bg-white rounded-xl border border-slate-200 p-6">
            @csrf
            @include('decks._form', ['selectedLevel' => $selectedLevel])

            <div class="mt-6 flex items-center justify-end gap-2">
                <a href="{{ route('levels.show', $selectedLevel) }}" class="px-4 py-2 rounded-md text-slate-600 hover:bg-slate-100 text-sm">إلغاء</a>
                <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 text-sm font-medium">حفظ المجموعة</button>
            </div>
        </form>
    </div>
@endsection
