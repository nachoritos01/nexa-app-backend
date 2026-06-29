<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Requests\Api\Agency\InvoiceRequest;
use App\Http\Resources\Agency\AgencyResource;
use App\Models\Agency\Invoice;
use Illuminate\Http\JsonResponse;

class InvoiceController extends AgencyCrudController
{
    protected function model(): string
    {
        return Invoice::class;
    }

    public function store(InvoiceRequest $request): JsonResponse
    {
        return $this->create($request->mapped());
    }

    public function update(InvoiceRequest $request, string $id): AgencyResource
    {
        return $this->modify($id, $request->mapped());
    }
}
