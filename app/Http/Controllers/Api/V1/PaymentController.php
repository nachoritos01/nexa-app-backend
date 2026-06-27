<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payment::with('order')
            ->whereHas('order', fn ($q) => $q->where('tenant_id', currentTenant()?->id));

        if ($request->filled('order_id')) {
            $query->where('order_id', $request->input('order_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $perPage = $request->integer('per_page', 15);
        $payments = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'data' => $payments->items(),
            'meta' => [
                'total' => $payments->total(),
                'page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }

    public function show(Payment $payment): JsonResponse
    {
        $payment->load('order');

        // Verify tenant access via order
        if ($payment->order?->tenant_id !== currentTenant()?->id) {
            return response()->json([
                'error' => 'Payment not found.',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        return response()->json([
            'data' => $payment,
        ]);
    }
}
