@props(['card', 'printSettings' => null])

@php
    $iconImageUrl = $card->icon_image_path
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($card->icon_image_path)
        : null;
    $hasIconImage = $card->show_icon && $iconImageUrl;
    $hasIconEmoji = $card->show_icon && ! $hasIconImage && $card->icon;
    $hasMedia = $hasIconImage || $hasIconEmoji;

    $unifyBg = ! empty($printSettings['unify_backgrounds'] ?? false)
        && ! empty($printSettings['unify_with_back'] ?? false);
    $unifiedBgValue = $printSettings['unified_bg_value'] ?? '#ffffff';
    $backFontPx = (int) ($printSettings['back_font_size_px'] ?? 0);

    $rootStyles = [];
    if ($unifyBg) {
        $rootStyles[] = 'background: ' . $unifiedBgValue;
    }
    $rootStyle = implode('; ', $rootStyles);

    $textStyle = $backFontPx > 0
        ? 'font-size: ' . $backFontPx . 'px; line-height: 1.25;'
        : '';
@endphp

<div {{ $attributes->merge(['class' => 'flashcard-face flashcard-back-split bg-white border border-slate-200 overflow-hidden']) }}
     @if ($rootStyle !== '') style="{{ $rootStyle }}" @endif>
    <div class="flashcard-back-media {{ $hasMedia ? '' : 'is-empty' }}"
         @if ($unifyBg) style="background: {{ $unifiedBgValue }}; border-bottom-color: rgba(0,0,0,0.08);" @endif>
        @if ($hasIconImage)
            <img src="{{ $iconImageUrl }}" alt="" class="flashcard-back-image">
        @elseif ($hasIconEmoji)
            <div class="flashcard-back-emoji">{{ $card->icon }}</div>
        @endif
    </div>
    <div class="flashcard-back-text">
        @if ($card->show_ar && $card->ar_meaning)
            <p class="flashcard-back-ar" @if ($textStyle !== '') style="{{ $textStyle }}" @endif>{{ $card->ar_meaning }}</p>
        @endif

        @if ($card->show_en && $card->en_meaning)
            <p class="flashcard-back-en" dir="ltr" @if ($textStyle !== '') style="{{ $textStyle }}" @endif>{{ $card->en_meaning }}</p>
        @endif

        @if ($card->show_explanation && $card->explanation)
            <p class="flashcard-back-explanation"
               @if ($textStyle !== '') style="font-size: {{ max(8, (int) round($backFontPx * 0.75)) }}px; line-height: 1.35;" @endif>{{ $card->explanation }}</p>
        @endif
    </div>
</div>
