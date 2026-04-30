@extends('layouts.app')

@section('title', 'مستوى جديد')

@section('content')
    <div class="max-w-xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">إنشاء مستوى جديد</h1>
        <p class="mb-4 text-sm text-slate-500">أنت تضيف مستوى جديدًا داخل اللغة: <span class="font-semibold text-slate-900">{{ $selectedLanguage->name }}</span></p>
        <form method="POST" action="{{ route('levels.store') }}" class="bg-white rounded-xl border border-slate-200 p-6">
            @csrf
            @include('levels._form', ['selectedLanguage' => $selectedLanguage])
            <div class="mt-6 flex items-center justify-end gap-2">
                <a href="{{ route('languages.show', $selectedLanguage) }}" class="px-4 py-2 rounded-md text-slate-600 hover:bg-slate-100 text-sm">إلغاء</a>
                <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 text-sm font-medium">حفظ المستوى</button>
            </div>
        </form>
    </div>
@endsection
