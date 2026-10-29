@props(['since'])

{{-- Time since the timer started, computed from the start on every tick so a locked phone loses nothing (F-06). --}}
@php($seconds = max(0, (int) $since->diffInSeconds(now())))
<span
    {{ $attributes->class('num tabular-nums') }}
    x-data="{ start: {{ $since->getTimestampMs() }}, now: Date.now() }"
    x-init="const tick = setInterval(() => now = Date.now(), 1000); $cleanup(() => clearInterval(tick))"
    x-text="((s) => [Math.floor(s / 3600), Math.floor(s % 3600 / 60), s % 60].map((n) => String(n).padStart(2, '0')).join(':'))(Math.max(0, Math.floor((now - start) / 1000)))"
>{{ sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60) }}</span>
