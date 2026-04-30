@props(['card'])

@php
    $iconImageUrl = $card->icon_image_path
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($card->icon_image_path)
        : null;
    $hasIconImage = $card->show_icon && $iconImageUrl;
    $hasIconEmoji = $card->show_icon && ! $hasIconImage && $card->icon;
    $hasMedia = $hasIconImage || $hasIconEmoji;
@endphp

<div {{ $attributes->merge(['class' => 'flashcard-face flashcard-back-split bg-white border border-slate-200 overflow-hidden']) }}>
    <div class="flashcard-back-media {{ $hasMedia ? '' : 'is-empty' }}">
        @if ($hasIconImage)
            <img src="{{ $iconImageUrl }}" alt="" class="flashcard-back-image">
        @elseif ($hasIconEmoji)
            <div class="flashcard-back-emoji">{{ $card->icon }}</div>
        @endif
    </div>
    <div class="flashcard-back-text">
        @if ($card->show_ar && $card->ar_meaning)
            <p class="flashcard-back-ar">{{ $card->ar_meaning }}</p>
        @endif

        @if ($card->show_en && $card->en_meaning)
            <p class="flashcard-back-en" dir="ltr">{{ $card->en_meaning }}</p>
        @endif

        @if ($card->show_explanation && $card->explanation)
            <p class="flashcard-back-explanation">{{ $card->explanation }}</p>
        @endif
    </div>
</div>
