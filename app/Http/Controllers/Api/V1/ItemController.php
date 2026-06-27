<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Item::query();

        if ($request->boolean('active', true)) {
            $query->active();
        }

        $perPage = $request->integer('per_page', 15);
        $items = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'data' => $items->items(),
            'meta' => [
                'total' => $items->total(),
                'page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    public function show(Item $item): JsonResponse
    {
        return response()->json([
            'data' => $item,
        ]);
    }
}
