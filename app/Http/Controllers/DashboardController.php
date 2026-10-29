<?php

namespace App\Http\Controllers;

use App\Domain\Billing\BillableEntry;
use App\Domain\Billing\BillingCalculator;
use App\Domain\Billing\Rounding\RoundUpPerPeriod;
use App\Domain\Provider\ProviderProfileRepository;
use App\Domain\Shared\HourlyRate;
use App\Domain\Shared\Minutes;
use App\Domain\Shared\Money;
use App\Domain\Shared\Points;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    /** Minutes worked in October 2026, from the spreadsheet the system replaces. */
    private const int SPREADSHEET_OCTOBER_MINUTES = 1324;

    /** Hourly rate charged in the spreadsheet, in cents. */
    private const int SPREADSHEET_RATE_CENTS = 6000;

    public function __invoke(Request $request, BillingCalculator $calculator, ProviderProfileRepository $profiles): View
    {
        /** @var User $user */
        $user = $request->user();

        $steps = [
            ['title' => __('Administrator account'), 'description' => __('You will manage users and permissions.'), 'done' => $user->is_admin, 'action' => null, 'version' => null],
            ['title' => __('Two-factor authentication'), 'description' => __('Protects the account that will hold the company certificate.'), 'done' => $user->two_factor_confirmed_at !== null, 'action' => route('security.edit'), 'version' => null],
            ['title' => __('Company details'), 'description' => __('Legal name, CNPJ and address for the report and the invoice.'), 'done' => $profiles->companyDetails()->isComplete(), 'action' => $user->can('settings.company.page') ? route('settings.company.edit') : null, 'version' => null],
            ['title' => __('First client and rate'), 'description' => __('Who is billed, and at what rate.'), 'done' => false, 'action' => null, 'version' => 'v0.3'],
            ['title' => __('First time entry'), 'description' => __('Timer or manual entry, from the phone or the computer.'), 'done' => false, 'action' => null, 'version' => 'v0.4'],
        ];

        $rate = HourlyRate::of(Money::fromCents(self::SPREADSHEET_RATE_CENTS));
        $worked = Minutes::of(self::SPREADSHEET_OCTOBER_MINUTES);
        $totals = $calculator->calculate([new BillableEntry($worked, $rate)], new RoundUpPerPeriod, $rate);

        return view('dashboard', [
            'steps' => $steps,
            'stepsDone' => count(array_filter($steps, fn (array $step): bool => $step['done'])),
            'example' => [
                'worked' => $worked->formatted(),
                'points' => $totals->points()?->value,
                'billed' => $totals->billedMinutes->formatted(),
                'rate' => $rate->perHour->formatted(),
                'pointValue' => $rate->pointValue()->formatted(),
                'amount' => $totals->amount->formatted(),
            ],
            'minutesPerPoint' => Points::MINUTES_PER_POINT,
        ]);
    }
}
