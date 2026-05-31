@extends('layouts.app')

@section('title', 'تعديل بطاقة')

@section('content')
    <div class="mb-4 text-sm text-slate-500">
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
        <a href="{{ route('categories.show', $category) }}" class="text-indigo-600 hover:underline">{{ $category->name }}</a>
    </div>

    <h1 class="text-2xl font-bold mb-6">تعديل البطاقة</h1>

    <form method="POST" action="{{ route('cards.update', $card) }}" enctype="multipart/form-data" class="bg-white rounded-xl border border-slate-200 p-6">
        @csrf
        @method('PUT')
        @include('cards._form', ['card' => $card, 'category' => $category, 'deck' => $deck])

        <div class="mt-6 flex items-center justify-end gap-2">
            <a href="{{ route('categories.show', $category) }}" class="px-4 py-2 rounded-md text-slate-600 hover:bg-slate-100 text-sm">إلغاء</a>
            <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 text-sm font-medium">حفظ التعديلات</button>
        </div>
    </form>
@endsection
