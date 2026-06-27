<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListOrdersRequest;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class OrderController extends Controller
{
    public function index(ListOrdersRequest $request): JsonResponse
    {
        $query = Order::with(['customer', 'lines.item']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'ilike', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $perPage = $request->integer('per_page', 15);
        $orders = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'total' => $orders->total(),
                'page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['customer', 'lines.item', 'payments']);

        return response()->json([
            'data' => $order,
        ]);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $total = 0;
        if (isset($validated['order_lines'])) {
            foreach ($validated['order_lines'] as $line) {
                $total += $line['quantity'] * $line['unit_price'];
            }
        }

        $order = Order::create([
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'customer_email' => $validated['customer_email'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'total' => $total,
            'subtotal' => $total,
            'total_paid' => 0,
            'status' => OrderStatus::Pending,
        ]);

        if (isset($validated['order_lines'])) {
            foreach ($validated['order_lines'] as $line) {
                $order->lines()->create([
                    'item_id' => $line['item_id'],
                    'description' => $line['description'] ?? null,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => $line['quantity'] * $line['unit_price'],
                ]);
            }
        }

        $order->load(['customer', 'lines.item']);

        return response()->json([
            'data' => $order,
        ], 201);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', new Enum(OrderStatus::class)],
        ]);

        $newStatus = OrderStatus::from($validated['status']);

        try {
            match ($newStatus) {
                OrderStatus::Confirmed => $order->confirm(),
                OrderStatus::Completed => $order->complete(),
                OrderStatus::Cancelled => $order->cancel(),
                default => $order->update(['status' => $newStatus]),
            };
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid status transition',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'data' => $order->fresh(),
            'message' => "Status updated to: {$newStatus->value}",
        ]);
    }
}
