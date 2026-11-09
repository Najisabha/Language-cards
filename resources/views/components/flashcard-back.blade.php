@props(['card', 'printSettings' => null])

@php
    $iconImageUrl = $card->iconImageUrl();
    $iconEmoji = $card->iconEmoji();
    $isPrint = is_array($printSettings);
    $hasPrintVisibility = $isPrint && array_key_exists('show_icon', $printSettings);

    $showIcon = $hasPrintVisibility ? ! empty($printSettings['show_icon']) : (bool) $card->show_icon;
    $showAr = $hasPrintVisibility ? ! empty($printSettings['show_ar']) : (bool) $card->show_ar;
    $showEn = $hasPrintVisibility ? ! empty($printSettings['show_en']) : (bool) $card->show_en;
    $showExplanation = $hasPrintVisibility
        ? ! empty($printSettings['show_explanation'])
        : (bool) $card->show_explanation;

    $hasIconImage = $showIcon && $iconImageUrl;
    $hasIconEmoji = $showIcon && $iconEmoji;
    $hasMediaData = (bool) ($iconImageUrl || $iconEmoji);
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
    <div class="flashcard-back-media {{ $hasMedia ? '' : ($isPrint ? 'print-part-hidden' : 'is-empty') }}"
         data-print-part="icon"
         @if ($unifyBg) style="background: {{ $unifiedBgValue }}; border-bottom-color: rgba(0,0,0,0.08);" @endif>
        @if ($isPrint && $hasMediaData)
            @if ($iconImageUrl)
                <img src="{{ $iconImageUrl }}" alt="" class="flashcard-back-image"
                     @if ($iconEmoji)
                     onerror="this.classList.add('hidden'); this.nextElementSibling?.classList.remove('hidden');"
                     @else
                     onerror="this.remove(); this.parentElement?.classList.add('print-part-hidden');"
                     @endif>
                @if ($iconEmoji)
                    <div class="flashcard-back-emoji hidden">{{ $iconEmoji }}</div>
                @endif
            @elseif ($iconEmoji)
                <div class="flashcard-back-emoji">{{ $iconEmoji }}</div>
            @endif
        @elseif ($hasIconImage)
            <img src="{{ $iconImageUrl }}" alt="" class="flashcard-back-image"
                 @if ($hasIconEmoji)
                 onerror="this.classList.add('hidden'); this.nextElementSibling?.classList.remove('hidden');"
                 @else
                 onerror="this.remove(); this.parentElement?.classList.add('is-empty');"
                 @endif>
            @if ($hasIconEmoji)
                <div class="flashcard-back-emoji hidden">{{ $iconEmoji }}</div>
            @endif
        @elseif ($hasIconEmoji)
            <div class="flashcard-back-emoji">{{ $iconEmoji }}</div>
        @endif
    </div>
    <div class="flashcard-back-text">
        @if ($isPrint ? $card->ar_meaning : ($showAr && $card->ar_meaning))
            <p class="flashcard-back-ar{{ $showAr ? '' : ' print-part-hidden' }}"
               data-print-part="ar"
               @if ($textStyle !== '') style="{{ $textStyle }}" @endif>{{ $card->ar_meaning }}</p>
        @endif

        @if ($isPrint ? $card->en_meaning : ($showEn && $card->en_meaning))
            <p class="flashcard-back-pron{{ $showEn ? '' : ' print-part-hidden' }}"
               data-print-part="en"
               @if ($textStyle !== '') style="{{ $textStyle }}" @endif>{{ $card->en_meaning }}</p>
        @endif

        @if ($isPrint ? $card->explanation : ($showExplanation && $card->explanation))
            <p class="flashcard-back-explanation{{ $showExplanation ? '' : ' print-part-hidden' }}"
               data-print-part="explanation"
               @if ($textStyle !== '') style="font-size: {{ max(8, (int) round($backFontPx * 0.75)) }}px; line-height: 1.35;" @endif>{{ $card->explanation }}</p>
        @endif
    </div>
</div>
