@extends('layouts.app')

@section('title', 'ترتيب بطاقات المجموعة')

@section('content')
    <div class="mb-4 text-sm text-slate-500">
        <a href="{{ route('decks.show', $deck) }}" class="text-indigo-600 hover:underline">{{ $deck->name }}</a>
    </div>

    <section class="page-hero mb-6">
        <div>
            <p class="page-eyebrow">{{ $deck->level?->name ?? 'Deck' }}</p>
            <h1 class="page-title">ترتيب بطاقات المجموعة</h1>
            <p class="page-subtitle">اسحب وأفلت أو أدخل رقم الترتيب يدويًا ثم احفظ.</p>
        </div>
    </section>

    <form method="POST" action="{{ route('decks.cards.reorder', $deck) }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        @csrf

        @if ($cards->isEmpty())
            <p class="text-slate-500 text-sm">لا توجد بطاقات لترتيبها.</p>
        @else
            <div id="sortable-cards" class="space-y-3">
                @foreach ($cards as $index => $card)
                    <div class="rounded-xl border border-slate-200 p-3 flex items-center gap-3 bg-white cursor-move"
                         draggable="true"
                         data-sortable-item>
                        <input type="hidden" name="cards[{{ $index }}][id]" value="{{ $card->id }}">
                        <div class="shrink-0 w-24 text-center">
                            <p class="text-xs text-slate-500 mb-1">الترتيب</p>
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-indigo-50 text-indigo-700 text-sm font-bold"
                                  data-order-label>{{ $index + 1 }}</span>
                            <input type="number"
                                   name="cards[{{ $index }}][position]"
                                   min="1"
                                   value="{{ $index + 1 }}"
                                   data-position-input
                                   class="mt-2 w-full rounded-md border border-slate-300 px-2 py-1 text-xs text-center">
                        </div>
                        <span class="text-slate-400 text-lg leading-none select-none" aria-hidden="true">⋮⋮</span>
                        <div class="grow min-w-0">
                            <p class="text-sm font-semibold text-slate-900 truncate" dir="ltr">{{ $card->word }}</p>
                            <p class="text-xs text-slate-500 mt-1">بطاقة رقم #{{ $card->id }}</p>
                        </div>
                        <a href="{{ route('cards.edit', $card) }}" class="text-xs rounded-md px-2 py-1 text-slate-600 hover:bg-slate-100">تعديل</a>
                    </div>
                @endforeach
            </div>

            @error('cards')
                <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
            @enderror
        @endif

        <div class="mt-5 flex items-center justify-end gap-2">
            <a href="{{ route('decks.show', $deck) }}" class="px-4 py-2 rounded-md text-slate-600 hover:bg-slate-100 text-sm">إلغاء</a>
            <button type="submit" class="btn btn-primary">حفظ الترتيب</button>
        </div>
    </form>
@endsection

<script>
(() => {
    const list = document.getElementById('sortable-cards');
    if (!list) return;

    let draggedItem = null;

    const refreshPositions = () => {
        const items = [...list.querySelectorAll('[data-sortable-item]')];
        items.forEach((item, index) => {
            const posInput = item.querySelector('[data-position-input]');
            const orderLabel = item.querySelector('[data-order-label]');
            if (posInput && posInput.dataset.manual !== '1') posInput.value = index + 1;
            if (orderLabel) orderLabel.textContent = index + 1;
        });
    };

    const getDragAfterElement = (container, y) => {
        const draggableElements = [...container.querySelectorAll('[data-sortable-item]:not(.is-dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset, element: child };
            }
            return closest;
        }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
    };

    list.addEventListener('dragstart', (event) => {
        const item = event.target.closest('[data-sortable-item]');
        if (!item) return;
        draggedItem = item;
        item.classList.add('is-dragging', 'opacity-60', 'ring-2', 'ring-indigo-300');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', item.querySelector('input[name$="[id]"]')?.value || 'drag');
    });

    list.addEventListener('dragend', () => {
        if (!draggedItem) return;
        draggedItem.classList.remove('is-dragging', 'opacity-60', 'ring-2', 'ring-indigo-300');
        draggedItem = null;
        refreshPositions();
    });

    list.addEventListener('dragover', (event) => {
        event.preventDefault();
        if (!draggedItem) return;
        const afterElement = getDragAfterElement(list, event.clientY);
        if (!afterElement) {
            list.appendChild(draggedItem);
        } else {
            list.insertBefore(draggedItem, afterElement);
        }
    });

    list.addEventListener('input', (event) => {
        const input = event.target.closest('[data-position-input]');
        if (!input) return;
        input.dataset.manual = '1';
    });

    list.closest('form')?.addEventListener('submit', () => {
        const items = [...list.querySelectorAll('[data-sortable-item]')];
        const parsed = items.map((item, domIndex) => {
            const posInput = item.querySelector('[data-position-input]');
            const raw = Number(posInput?.value || 0);
            return {
                item,
                position: Number.isFinite(raw) && raw > 0 ? raw : domIndex + 1,
            };
        });

        parsed.sort((a, b) => a.position - b.position);
        parsed.forEach(({ item }, index) => {
            list.appendChild(item);
            const posInput = item.querySelector('[data-position-input]');
            const orderLabel = item.querySelector('[data-order-label]');
            if (posInput) posInput.value = index + 1;
            if (orderLabel) orderLabel.textContent = index + 1;
        });
    });

    refreshPositions();
})();
</script>

