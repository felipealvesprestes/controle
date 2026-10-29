@props(['color'])

{{-- Colour marker of a client or a category (F-03, F-04). Class names are literal so Tailwind keeps them. --}}
@php
    $classes = [
        'petrol' => 'bg-tag-petrol',
        'green' => 'bg-tag-green',
        'amber' => 'bg-tag-amber',
        'red' => 'bg-tag-red',
        'violet' => 'bg-tag-violet',
        'blue' => 'bg-tag-blue',
        'pink' => 'bg-tag-pink',
        'slate' => 'bg-tag-slate',
    ];
@endphp
<span {{ $attributes->class(['inline-block size-2.5 shrink-0 rounded-full', $classes[$color] ?? $classes['slate']]) }} aria-hidden="true"></span>
