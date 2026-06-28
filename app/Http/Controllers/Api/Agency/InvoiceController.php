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
        $invoice = Invoice::create($request->mapped());

        return (new AgencyResource($invoice))->response()->setStatusCode(201);
    }

    public function update(InvoiceRequest $request, string $id): AgencyResource
    {
        $invoice = $this->find($id);
        $invoice->update($request->mapped());

        return new AgencyResource($invoice);
    }
}
