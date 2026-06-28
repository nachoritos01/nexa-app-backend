<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Requests\Api\Agency\SupplierRequest;
use App\Http\Resources\Agency\AgencyResource;
use App\Models\Agency\Supplier;
use Illuminate\Http\JsonResponse;

class SupplierController extends AgencyCrudController
{
    protected function model(): string
    {
        return Supplier::class;
    }

    public function store(SupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::create($request->mapped());

        return (new AgencyResource($supplier))->response()->setStatusCode(201);
    }

    public function update(SupplierRequest $request, string $id): AgencyResource
    {
        $supplier = $this->find($id);
        $supplier->update($request->mapped());

        return new AgencyResource($supplier);
    }
}
