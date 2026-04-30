@props(['card'])

@php
    $value = (string) $card->front_bg_value;
    $isGradient = (bool) preg_match('/^(linear|radial|conic)-gradient/i', $value);

    if ($card->front_bg_type === 'image') {
        $bgStyle = "background-image: url('" . e($value) . "'); background-size: cover; background-position: center;";
    } elseif ($isGradient) {
        $bgStyle = 'background: ' . $value . ';';
    } else {
        $bgStyle = 'background-color: ' . $value . ';';
    }
@endphp

<div {{ $attributes->merge(['class' => 'flashcard-face relative flex items-center justify-center text-center p-3 border border-slate-200']) }}
     style="{{ $bgStyle }}">
    @if ($card->front_bg_type === 'image')
        <div class="absolute inset-0 bg-slate-900/15"></div>
    @endif
    <span class="relative z-10 text-2xl md:text-3xl font-extrabold text-slate-900 drop-shadow-sm" dir="ltr">
        {{ $card->word }}
    </span>
</div>
