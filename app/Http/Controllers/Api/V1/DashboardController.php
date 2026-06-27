<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $tenant = currentTenant();

        $ordersThisMonth = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $revenueThisMonth = Payment::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        return response()->json([
            'data' => [
                'orders_this_month' => $ordersThisMonth,
                'revenue_this_month' => (float) $revenueThisMonth,
                'plan' => $tenant->plan,
                'usage' => $tenant->usageCounts(),
                'limits' => $tenant->planLimits(),
            ],
        ]);
    }
}
