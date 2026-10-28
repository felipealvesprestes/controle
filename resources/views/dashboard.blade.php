<x-layouts::app :title="__('Dashboard')">
    <x-ui.page-header
        :title="__('Dashboard')"
        :description="__(':done of :total setup steps done', ['done' => $stepsDone, 'total' => count($steps)])"
        :breadcrumbs="[config('app.name') => route('dashboard'), __('Dashboard') => null]"
    />

    <div class="grid gap-6 p-4 sm:p-8 lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start">
        {{-- Setup: the screen's question is "what is left before I can bill?" --}}
        <section class="rounded-lg border border-line bg-surface" aria-labelledby="setup-title">
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 pt-5 sm:px-6">
                <h2 id="setup-title" class="text-base font-medium text-ink">{{ __('Initial setup') }}</h2>
                <p class="num text-sm text-muted">{{ round($stepsDone / count($steps) * 100) }}%</p>
            </div>

            <div class="mx-4 mt-3 h-1.5 sm:mx-6 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/10" role="progressbar" aria-valuemin="0" aria-valuemax="{{ count($steps) }}" aria-valuenow="{{ $stepsDone }}" aria-label="{{ __('Initial setup') }}">
                <div class="h-full rounded-full bg-brand transition-[width] duration-500" style="width: {{ $stepsDone / count($steps) * 100 }}%"></div>
            </div>

            <ol class="mt-4 divide-y divide-line">
                @foreach ($steps as $step)
                    <li class="flex items-start gap-4 px-4 py-4 sm:items-center sm:px-6">
                        @if ($step['done'])
                            <flux:icon.check-circle variant="solid" class="size-6 shrink-0 text-brand" />
                        @elseif ($step['version'])
                            <flux:icon.clock variant="outline" class="size-6 shrink-0 text-zinc-400" />
                        @else
                            <span class="size-6 shrink-0 rounded-full border-2 border-zinc-300 dark:border-zinc-600" aria-hidden="true"></span>
                        @endif

                        <div class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium {{ $step['version'] ? 'text-muted' : 'text-ink' }}">
                                {{ $step['title'] }}
                                <span class="sr-only">— {{ $step['done'] ? __('done') : __('pending') }}</span>
                            </p>
                            <p class="mt-0.5 text-sm text-muted">{{ $step['description'] }}</p>
                        </div>

                        @if ($step['done'])
                            <span class="text-sm text-brand">{{ __('Done') }}</span>
                        @elseif ($step['action'])
                            <flux:button size="sm" :href="$step['action']" wire:navigate class="self-start sm:self-auto !border-brand/40 !text-brand hover:!bg-brand-soft">{{ __('Set up') }}</flux:button>
                        @else
                            <span class="self-start rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs text-muted sm:self-auto dark:bg-white/10">{{ __('Arrives in :version', ['version' => $step['version']]) }}</span>
                        @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Billing rule, computed by the domain with the spreadsheet's real October. --}}
        <section class="rounded-lg border border-line bg-surface" aria-labelledby="rule-title">
            <div class="px-6 pt-5">
                <h2 id="rule-title" class="text-base font-medium text-ink">{{ __('Billing rule') }}</h2>
                <p class="mt-1 text-sm text-muted">{{ __('1 point is :minutes minutes; the month is rounded up once.', ['minutes' => $minutesPerPoint]) }}</p>
            </div>

            <div class="mt-5 border-t border-line px-6 py-5">
                <p class="text-sm text-muted">{{ __('October 2026, from the spreadsheet') }}</p>
                <p class="num mt-1 text-[30px] leading-tight font-normal tracking-[-0.01em] text-brand">{{ $example['amount'] }}</p>

                <dl class="mt-4 space-y-2.5 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">{{ __('Worked') }}</dt>
                        <dd class="num text-ink">{{ $example['worked'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">{{ __('Billed') }}</dt>
                        <dd class="num text-ink">{{ trans_choice(':count point|:count points', $example['points'] ?? 0) }} · {{ $example['billed'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">{{ __('Rate') }}</dt>
                        <dd class="num text-ink">{{ $example['rate'] }}/h · {{ $example['pointValue'] }} {{ __('per point') }}</dd>
                    </div>
                </dl>
            </div>
        </section>
    </div>
</x-layouts::app>
