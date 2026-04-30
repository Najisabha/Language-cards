@extends('layouts.app')

@section('title', 'تعديل تصنيف')

@section('content')
    <div class="max-w-xl mx-auto">
        <div class="mb-4 text-sm text-slate-500">
            <a href="{{ route('decks.show', $deck) }}" class="text-indigo-600 hover:underline">{{ $deck->name }}</a>
        </div>

        <h1 class="text-2xl font-bold mb-6">تعديل التصنيف</h1>
        <form method="POST" action="{{ route('categories.update', $category) }}" class="bg-white rounded-xl border border-slate-200 p-6">
            @csrf
            @method('PUT')
            @include('categories._form', ['deck' => $deck, 'category' => $category])
            <div class="mt-6 flex items-center justify-end gap-2">
                <a href="{{ route('decks.show', $deck) }}" class="px-4 py-2 rounded-md text-slate-600 hover:bg-slate-100 text-sm">إلغاء</a>
                <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 text-sm font-medium">حفظ التعديلات</button>
            </div>
        </form>
    </div>
@endsection
