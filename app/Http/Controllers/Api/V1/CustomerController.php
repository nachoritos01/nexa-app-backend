<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerRequest;
use App\Http\Requests\Api\V1\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        $perPage = $request->integer('per_page', 15);
        $customers = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'data' => $customers->items(),
            'meta' => [
                'total' => $customers->total(),
                'page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'last_page' => $customers->lastPage(),
            ],
        ]);
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->loadCount('orders');

        return response()->json([
            'data' => $customer,
        ]);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());

        return response()->json([
            'data' => $customer,
        ], 201);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer->update($request->validated());

        return response()->json([
            'data' => $customer,
        ]);
    }

    public function orders(Request $request, Customer $customer): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $orders = $customer->orders()
            ->orderByDesc('created_at')
            ->paginate($perPage);

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
}
