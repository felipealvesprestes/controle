<a {{ $attributes->merge(['class' => 'flex items-center gap-2.5 rounded-md text-ink']) }}>
    <span class="flex size-7 items-center justify-center rounded-md bg-brand text-white dark:text-zinc-950">
        <x-app-logo-icon class="size-[18px]" />
    </span>
    <span class="text-[17px] font-medium tracking-[-0.01em]">{{ config('app.name') }}</span>
</a>
