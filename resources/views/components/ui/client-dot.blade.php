@props(['color'])

{{-- Colour marker of a client (F-03). Class names are literal so Tailwind keeps them. --}}
@php
    $classes = [
        'petrol' => 'bg-client-petrol',
        'green' => 'bg-client-green',
        'amber' => 'bg-client-amber',
        'red' => 'bg-client-red',
        'violet' => 'bg-client-violet',
        'blue' => 'bg-client-blue',
        'pink' => 'bg-client-pink',
        'slate' => 'bg-client-slate',
    ];
@endphp
<span {{ $attributes->class(['inline-block size-2.5 shrink-0 rounded-full', $classes[$color] ?? $classes['slate']]) }} aria-hidden="true"></span>
