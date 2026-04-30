@extends('layouts.app')

@section('title', 'تعديل مجموعة')

@section('content')
    <div class="max-w-xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">تعديل المجموعة</h1>

        <form method="POST" action="{{ route('decks.update', $deck) }}" class="bg-white rounded-xl border border-slate-200 p-6">
            @csrf
            @method('PUT')
            @include('decks._form', ['deck' => $deck, 'selectedLevel' => $deck->level])

            <div class="mt-6 flex items-center justify-end gap-2">
                <a href="{{ route('decks.show', $deck) }}" class="px-4 py-2 rounded-md text-slate-600 hover:bg-slate-100 text-sm">إلغاء</a>
                <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 text-sm font-medium">حفظ التعديلات</button>
            </div>
        </form>

        <form method="POST" action="{{ route('decks.destroy', $deck) }}" class="mt-4 text-end" onsubmit="return confirm('هل أنت متأكد من حذف هذه المجموعة وكل تصنيفاتها وبطاقاتها؟');">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 rounded-md text-red-600 hover:bg-red-50 text-sm">حذف المجموعة</button>
        </form>
    </div>
@endsection
