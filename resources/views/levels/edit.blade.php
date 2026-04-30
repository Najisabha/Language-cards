@extends('layouts.app')

@section('title', 'تعديل مستوى')

@section('content')
    <div class="max-w-xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">تعديل المستوى</h1>
        <form method="POST" action="{{ route('levels.update', $level) }}" class="bg-white rounded-xl border border-slate-200 p-6">
            @csrf
            @method('PUT')
            @include('levels._form', ['level' => $level, 'selectedLanguage' => $level->language])
            <div class="mt-6 flex items-center justify-end gap-2">
                <a href="{{ route('levels.show', $level) }}" class="px-4 py-2 rounded-md text-slate-600 hover:bg-slate-100 text-sm">إلغاء</a>
                <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 text-sm font-medium">حفظ التعديلات</button>
            </div>
        </form>
        <form method="POST" action="{{ route('levels.destroy', $level) }}" class="mt-4 text-end" onsubmit="return confirm('حذف المستوى؟ سيتم فصل المجموعات عنه فقط.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 rounded-md text-red-600 hover:bg-red-50 text-sm">حذف المستوى</button>
        </form>
    </div>
@endsection
