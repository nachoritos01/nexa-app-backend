<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Requests\Api\Agency\InvoiceRequest;
use App\Http\Resources\Agency\AgencyResource;
use App\Models\Agency\Invoice;
use App\Services\Agency\AgencyPdfGenerator;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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

    public function pdf(string $id, AgencyPdfGenerator $pdf): Response
    {
        /** @var Invoice $invoice */
        $invoice = $this->find($id);

        return $pdf->invoiceInline($invoice)->download("factura_{$invoice->number}.pdf");
    }
}
