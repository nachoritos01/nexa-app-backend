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
        return $this->create($request->mapped());
    }

    public function update(SupplierRequest $request, string $id): AgencyResource
    {
        return $this->modify($id, $request->mapped());
    }
}
