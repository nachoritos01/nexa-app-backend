<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Requests\Api\Agency\InvoiceRequest;
use App\Http\Resources\Agency\AgencyResource;
use App\Mail\Agency\AgencyDocumentMail;
use App\Models\Agency\Client;
use App\Models\Agency\Invoice;
use App\Services\Agency\AgencyPdfGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
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

        return $pdf->invoiceInline($invoice)->download('factura_'.$this->safeFilename($invoice->number).'.pdf');
    }

    public function send(string $id, AgencyPdfGenerator $pdf): JsonResponse
    {
        /** @var Invoice $invoice */
        $invoice = $this->find($id);
        $client = Client::find($invoice->client_id);

        if ($client === null || blank($client->email)) {
            return response()->json(['message' => 'El cliente no tiene un email registrado.'], 422);
        }

        $filename = 'factura_'.$this->safeFilename($invoice->number).'.pdf';
        Mail::to($client->email)->send(new AgencyDocumentMail(
            documentType: 'Factura',
            number: $invoice->number,
            clientName: $client->name,
            businessName: $pdf->business()['name'],
            pdfBytes: $pdf->invoiceInline($invoice)->output(),
            pdfFilename: $filename,
        ));

        return response()->json(['message' => 'Factura enviada a '.$client->email]);
    }
}
