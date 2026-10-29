<?php

namespace App\Livewire\Time;

use App\Models\TimeEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The running timer on every screen but Today, so it is never forgotten (F-06).
 */
class TimerBar extends Component
{
    #[Computed]
    public function timer(): ?TimeEntry
    {
        if (Gate::denies('time.entry.create')) {
            return null;
        }

        return TimeEntry::query()->with('client')->where('user_id', Auth::id())->whereNull('ended_at')->first();
    }

    public function render(): View
    {
        return view('livewire.time.timer-bar');
    }
}
