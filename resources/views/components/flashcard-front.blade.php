@props(['card', 'printSettings' => null])

@php
    $value = (string) $card->front_bg_value;
    $isGradient = (bool) preg_match('/^(linear|radial|conic)-gradient/i', $value);

    $unifyBg = ! empty($printSettings['unify_backgrounds'] ?? false);
    $unifiedBgValue = $printSettings['unified_bg_value'] ?? '#ffffff';
    $rotate = $printSettings['front_text_rotate'] ?? 'none';
    $frontFontPx = (int) ($printSettings['front_font_size_px'] ?? 0);
    $useImageOverlay = false;

    if ($unifyBg) {
        $bgStyle = 'background: ' . $unifiedBgValue . ';';
    } elseif ($card->front_bg_type === 'image') {
        $bgStyle = "background-image: url('" . e($value) . "'); background-size: cover; background-position: center;";
        $useImageOverlay = true;
    } elseif ($isGradient) {
        $bgStyle = 'background: ' . $value . ';';
    } else {
        $bgStyle = 'background-color: ' . $value . ';';
    }

    $textStyles = [];
    if ($frontFontPx > 0) {
        $textStyles[] = 'font-size: ' . $frontFontPx . 'px';
        $textStyles[] = 'line-height: 1.1';
    }
    if ($rotate === '90' || $rotate === '-90') {
        $textStyles[] = 'transform: rotate(' . $rotate . 'deg)';
        $textStyles[] = 'display: inline-block';
        $textStyles[] = 'transform-origin: center center';
        $textStyles[] = 'white-space: nowrap';
    }
    $textStyle = implode('; ', $textStyles);
@endphp

<div {{ $attributes->merge(['class' => 'flashcard-face relative flex items-center justify-center text-center p-3 border border-slate-200']) }}
     style="{{ $bgStyle }}">
    @if ($useImageOverlay)
        <div class="absolute inset-0 bg-slate-900/15"></div>
    @endif
    <span class="flashcard-front-word relative z-10 font-extrabold text-slate-900 drop-shadow-sm{{ $textStyle === '' ? ' text-2xl md:text-3xl' : '' }}"
          dir="ltr"
          @if ($textStyle !== '') style="{{ $textStyle }}" @endif>
        {{ $card->word }}
    </span>
</div>
