<?php

namespace App\Filament\Pages;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class CashRegister extends Page
{
    protected static ?string $title = 'Cash Register';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Cash Register';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 50;

    protected static string $view = 'filament.pages.cash-register';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $preset = 'today';

    public static function canAccess(): bool
    {
        if (! hasModule('payments')) {
            return false;
        }

        return auth()->user()?->can('payments.view') ?? false;
    }

    public function mount(): void
    {
        $this->dateFrom = now()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function applyPreset(string $preset): void
    {
        $this->preset = $preset;

        match ($preset) {
            'today' => $this->setDates(now(), now()),
            'week' => $this->setDates(now()->startOfWeek(), now()),
            'month' => $this->setDates(now()->startOfMonth(), now()),
            default => null,
        };
    }

    private function setDates(Carbon $from, Carbon $to): void
    {
        $this->dateFrom = $from->format('Y-m-d');
        $this->dateTo = $to->format('Y-m-d');
    }

    public function getViewData(): array
    {
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to = Carbon::parse($this->dateTo)->endOfDay();

        // Current period payments by method
        $paymentsByMethod = Payment::query()
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('orders.tenant_id', currentTenant()?->id)
            ->whereBetween('payments.received_at', [$from, $to])
            ->whereNull('orders.deleted_at')
            ->selectRaw('payments.method, SUM(payments.amount) as total, COUNT(*) as count')
            ->groupBy('payments.method')
            ->get()
            ->keyBy('method');

        $grandTotal = $paymentsByMethod->sum('total');
        $totalCount = $paymentsByMethod->sum('count');

        // Previous period for comparison
        $periodDays = $from->diffInDays($to) + 1;
        $prevFrom = $from->copy()->subDays($periodDays);
        $prevTo = $to->copy()->subDays($periodDays);

        $prevTotal = Payment::query()
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('orders.tenant_id', currentTenant()?->id)
            ->whereBetween('payments.received_at', [$prevFrom, $prevTo])
            ->whereNull('orders.deleted_at')
            ->sum('payments.amount');

        $percentChange = $prevTotal > 0
            ? round((($grandTotal - $prevTotal) / $prevTotal) * 100, 1)
            : ($grandTotal > 0 ? 100 : 0);

        // Detailed payments list
        $payments = Payment::query()
            ->with('order')
            ->whereBetween('received_at', [$from, $to])
            ->orderByDesc('received_at')
            ->limit(50)
            ->get();

        // Build method cards data
        $methods = [];
        foreach (PaymentMethod::cases() as $method) {
            $data = $paymentsByMethod->get($method->value);
            $methods[] = [
                'label' => $method->label(),
                'value' => $method->value,
                'color' => $method->color(),
                'total' => $data ? (float) $data->total : 0,
                'count' => $data ? (int) $data->count : 0,
            ];
        }

        return [
            'methods' => $methods,
            'grandTotal' => $grandTotal,
            'totalCount' => $totalCount,
            'percentChange' => $percentChange,
            'prevTotal' => $prevTotal,
            'payments' => $payments,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'preset' => $this->preset,
        ];
    }
}
