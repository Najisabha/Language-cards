@extends('layouts.app')

@section('title', 'لغة جديدة')

@section('content')
    <div class="max-w-xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">إنشاء لغة جديدة</h1>
        <form method="POST" action="{{ route('languages.store') }}" class="bg-white rounded-xl border border-slate-200 p-6">
            @csrf
            @include('languages._form')
            <div class="mt-6 flex items-center justify-end gap-2">
                <a href="{{ route('languages.index') }}" class="px-4 py-2 rounded-md text-slate-600 hover:bg-slate-100 text-sm">إلغاء</a>
                <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 text-sm font-medium">حفظ اللغة</button>
            </div>
        </form>
    </div>
@endsection
