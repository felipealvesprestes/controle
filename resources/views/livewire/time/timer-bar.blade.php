<div>
    @if ($this->timer !== null)
        <div class="sticky top-14 z-20 flex items-center gap-3 border-b border-line bg-brand-soft px-4 py-2 text-sm sm:px-8" data-test="timer-bar">
            <x-ui.color-dot :color="$this->timer->client->color" />
            <span class="min-w-0 truncate font-medium text-ink">{{ $this->timer->client->name }}</span>
            <x-ui.elapsed :since="$this->timer->started_at" class="text-ink" />
            <flux:button class="ms-auto" size="xs" icon="stop" :href="route('time.today', ['stop' => 1])" wire:navigate>{{ __('Stop') }}</flux:button>
        </div>
    @endif
</div>
