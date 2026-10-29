<?php

namespace App\Livewire\Time;

use App\Domain\Shared\Minutes;
use App\Livewire\Concerns\EditsTimeEntries;
use App\Models\Category;
use App\Models\Client;
use App\Models\TimeEntry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Component;

/**
 * T-03: entries of a month, filtered and grouped by day (F-05).
 * Without time.entry.scope.all a person only sees their own (SEC-03).
 *
 * @property-read array<string, list<TimeEntry>> $days
 * @property-read bool $seesEveryone
 */
class TimeEntriesIndex extends Component
{
    use EditsTimeEntries;

    /** Y-m; the filters are remembered for the session. */
    #[Session]
    public string $month = '';

    #[Session]
    public string $filterClientId = '';

    #[Session]
    public string $filterCategoryId = '';

    #[Session]
    public string $search = '';

    public function mount(): void
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->month) !== 1) {
            $this->month = CarbonImmutable::now(Config::string('app.display_timezone'))->format('Y-m');
        }
    }

    public function clearFilters(): void
    {
        $this->reset('filterClientId', 'filterCategoryId', 'search');
    }

    /**
     * @return array<string, list<TimeEntry>> Keyed by local day, newest first.
     */
    #[Computed]
    public function days(): array
    {
        $zone = Config::string('app.display_timezone');
        $days = [];

        foreach ($this->query()->with(['client', 'category', 'billingPeriod', 'user'])->orderByDesc('started_at')->get() as $entry) {
            $days[$entry->started_at->timezone($zone)->format('Y-m-d')][] = $entry;
        }

        return $days;
    }

    /**
     * @return array{duration: string, count: int}
     */
    #[Computed]
    public function totals(): array
    {
        $entries = array_merge(...array_values($this->days));

        return [
            'duration' => self::sum($entries),
            'count' => count($entries),
        ];
    }

    /**
     * Every client and category with entries, active or not, for the filters.
     *
     * @return array{clients: list<array{id: int, name: string}>, categories: list<array{id: int, name: string}>}
     */
    #[Computed]
    public function filterOptions(): array
    {
        return [
            'clients' => array_values(Client::query()->orderBy('name')->get(['id', 'name'])->map(fn (Client $client): array => ['id' => $client->id, 'name' => $client->name])->all()),
            'categories' => array_values(Category::query()->orderBy('sort_order')->get(['id', 'name'])->map(fn (Category $category): array => ['id' => $category->id, 'name' => $category->name])->all()),
        ];
    }

    #[Computed]
    public function seesEveryone(): bool
    {
        return Gate::allows('time.entry.scope.all');
    }

    /**
     * @param  list<TimeEntry>  $entries
     */
    public static function sum(array $entries): string
    {
        return Minutes::of(array_sum(array_map(fn (TimeEntry $entry): int => (int) $entry->duration_minutes, $entries)))->formatted();
    }

    protected function entriesChanged(): void
    {
        unset($this->days, $this->totals);
    }

    /**
     * @return Builder<TimeEntry>
     */
    private function query(): Builder
    {
        $zone = Config::string('app.display_timezone');
        $start = CarbonImmutable::createFromFormat('!Y-m', $this->month, $zone) ?: CarbonImmutable::now($zone)->startOfMonth();
        $search = trim($this->search);

        return TimeEntry::query()
            ->whereNotNull('ended_at')
            ->where('started_at', '>=', $start->utc())
            ->where('started_at', '<', $start->addMonth()->utc())
            ->when(! $this->seesEveryone, fn (Builder $query) => $query->where('user_id', Auth::id()))
            ->when($this->filterClientId !== '', fn (Builder $query) => $query->where('client_id', (int) $this->filterClientId))
            ->when($this->filterCategoryId !== '', fn (Builder $query) => $query->where('category_id', (int) $this->filterCategoryId))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('description', 'like', '%'.addcslashes($search, '%_\\').'%')
                ->orWhere('reference', 'like', '%'.addcslashes($search, '%_\\').'%')));
    }

    public function render(): View
    {
        return view('livewire.time.time-entries-index')->title(__('Time entries'));
    }
}
