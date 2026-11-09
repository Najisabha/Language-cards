@extends('layouts.app')

@section('title', 'استيراد بيانات - ' . $language->name)

@section('content')
    <div class="mb-4 text-sm text-slate-500">
        <a href="{{ route('languages.index') }}" class="text-indigo-600 hover:underline">اللغات</a>
        <span class="mx-1">/</span>
        <a href="{{ route('languages.show', $language) }}" class="text-indigo-600 hover:underline">{{ $language->name }}</a>
        <span class="mx-1">/</span>
        <span>استيراد</span>
    </div>

    <section class="page-hero mb-8">
        <div>
            <p class="page-eyebrow">استيراد Excel</p>
            <h1 class="page-title">{{ $language->name }}</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('languages.import.template', $language) }}" class="btn btn-secondary">تحميل قالب فارغ</a>
            <a href="{{ route('languages.show', $language) }}" class="btn btn-secondary">رجوع</a>
        </div>
    </section>

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-sm">
            <ul class="list-disc pr-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="mb-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-bold text-slate-900">رفع الملف</h2>

        <form action="{{ route('languages.import', $language) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label for="file" class="mb-2 block text-sm font-semibold text-slate-700">ملف Excel</label>
                <input
                    id="file"
                    type="file"
                    name="file"
                    accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                    required
                    class="block w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm file:me-4 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-indigo-700"
                >
            </div>
            <button type="submit" class="btn btn-primary">بدء الاستيراد</button>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-slate-50 p-6 text-sm text-slate-600 shadow-sm">
        <h2 class="mb-3 text-base font-bold text-slate-900">ملاحظات مهمة</h2>
        <ul class="list-disc space-y-2 pr-5">
            <li>الأعمدة: <strong>المستوى</strong>، <strong>العنوان الأساسي</strong>، <strong>النوع</strong>، <strong>الفئة</strong>، <strong>الكلمة</strong>، المعنى الإنجليزي، المعنى العربي، الشرح، الأيقونة.</li>
            <li>الأعمدة المطلوبة: <strong>المستوى</strong> و<strong>النوع</strong> و<strong>الكلمة</strong>. عمود <strong>العنوان الأساسي</strong> اختياري.</li>
            <li>إذا كان المستوى أو النوع غير موجود يُنشأ تلقائياً، ويُحدَّث عنوان المستوى عند وجوده في الملف.</li>
            <li>إذا كانت الفئة فارغة تُستخدم «الكلمات» تلقائياً.</li>
            <li>باقي الإعدادات (اللون، الخلفية، الإظهار، الترتيب) تُضبط تلقائياً بالقيم الافتراضية.</li>
            <li>الكلمة الموجودة مسبقاً في هذه اللغة تُستبدل ببيانات ملف Excel، والجديدة تُضاف.</li>
            <li>إذا وُجدت كلمات مكررة داخل الملف ستظهر صفحة تأكيد قبل الاستيراد، ويُبقى أول ظهور لكل كلمة.</li>
        </ul>
    </section>
@endsection
