@props([
    'title',
    'color' => '#64748b',
    'href' => null,
    'type' => 'button',
])

@php
    $classes = 'inline-flex h-10 w-10 items-center justify-center rounded-full shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-45';
@endphp

@if ($href)
    <a href="{{ $href }}"
       title="{{ $title }}"
       aria-label="{{ $title }}"
       {{ $attributes->merge(['class' => $classes]) }}
       style="background-color: {{ $color }};">
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}"
            title="{{ $title }}"
            aria-label="{{ $title }}"
            {{ $attributes->merge(['class' => $classes]) }}
            style="background-color: {{ $color }};">
        {{ $slot }}
    </button>
@endif
