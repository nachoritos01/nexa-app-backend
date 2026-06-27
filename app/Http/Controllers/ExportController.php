<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FeatureUsage;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct()
    {
        abort_unless(hasModule('exports'), 404);
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        $tenant = currentTenant();

        if (! $tenant) {
            abort(403, 'No tenant selected.');
        }

        if ($tenant->plan === 'starter') {
            abort(403, 'Export is not available on the Starter plan. Please upgrade.');
        }

        if (! $request->user()?->can('reports.export')) {
            abort(403, 'You do not have permission to export.');
        }

        FeatureUsage::track('export_csv');

        return match ($type) {
            'orders' => $this->exportOrders(),
            'customers' => $this->exportCustomers(),
            'payments' => $this->exportPayments(),
            'profitability' => $this->exportProfitability($request),
            default => abort(404, 'Unknown export type.'),
        };
    }

    private function exportOrders(): StreamedResponse
    {
        $filename = 'orders-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Customer', 'Phone', 'Total', 'Paid', 'Balance', 'Status', 'Location', 'Date']);

            Order::query()->orderBy('id')->chunk(500, function ($orders) use ($handle) {
                foreach ($orders as $order) {
                    fputcsv($handle, [
                        $order->id,
                        $order->customer_name,
                        $order->customer_phone,
                        $order->total,
                        $order->total_paid,
                        $order->balance,
                        $order->status->value,
                        $order->location_id ?? '',
                        $order->created_at->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function exportCustomers(): StreamedResponse
    {
        $filename = 'customers-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'Phone', 'Email', 'Orders', 'Registration Date']);

            Customer::query()->withCount('orders')->orderBy('id')->chunk(500, function ($customers) use ($handle) {
                foreach ($customers as $customer) {
                    fputcsv($handle, [
                        $customer->id,
                        $customer->name,
                        $customer->phone,
                        $customer->email ?? '',
                        $customer->orders_count,
                        $customer->created_at->format('Y-m-d'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function exportPayments(): StreamedResponse
    {
        $filename = 'payments-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Order', 'Amount', 'Method', 'Reference', 'Date']);

            Payment::query()->with('order')->orderBy('id')->chunk(500, function ($payments) use ($handle) {
                foreach ($payments as $payment) {
                    fputcsv($handle, [
                        $payment->id,
                        $payment->order_id,
                        $payment->amount,
                        $payment->method?->value ?? '',
                        $payment->reference ?? '',
                        $payment->received_at?->format('Y-m-d H:i') ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function exportProfitability(Request $request): StreamedResponse
    {
        $month = $request->query('month', now()->format('Y-m'));
        [$year, $monthNum] = explode('-', $month);
        $start = now()->setDate((int) $year, (int) $monthNum, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $filename = 'profitability-' . $month . '.csv';

        return response()->streamDownload(function () use ($start, $end) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Item', 'Units Sold', 'Revenue', '% of Total']);

            $products = OrderLine::query()
                ->join('orders', 'order_lines.order_id', '=', 'orders.id')
                ->join('items', 'order_lines.item_id', '=', 'items.id')
                ->where('orders.tenant_id', currentTenant()?->id)
                ->whereBetween('orders.created_at', [$start, $end])
                ->whereNull('orders.deleted_at')
                ->selectRaw('items.name, SUM(order_lines.quantity) as total_qty, SUM(order_lines.subtotal) as total_revenue')
                ->groupBy('items.id', 'items.name')
                ->orderByDesc('total_revenue')
                ->get();

            $grandTotal = $products->sum('total_revenue');

            foreach ($products as $product) {
                $percentage = $grandTotal > 0 ? round(($product->total_revenue / $grandTotal) * 100, 1) : 0;
                fputcsv($handle, [
                    $product->name,
                    $product->total_qty,
                    $product->total_revenue,
                    $percentage . '%',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
