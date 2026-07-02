<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Requests\Api\Agency\QuoteRequest;
use App\Http\Resources\Agency\AgencyResource;
use App\Mail\Agency\AgencyDocumentMail;
use App\Models\Agency\Client;
use App\Models\Agency\Quote;
use App\Services\Agency\AgencyPdfGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class QuoteController extends AgencyCrudController
{
    protected function model(): string
    {
        return Quote::class;
    }

    public function store(QuoteRequest $request): JsonResponse
    {
        return $this->create($request->mapped());
    }

    public function update(QuoteRequest $request, string $id): AgencyResource
    {
        return $this->modify($id, $request->mapped());
    }

    public function pdf(string $id, AgencyPdfGenerator $pdf): Response
    {
        /** @var Quote $quote */
        $quote = $this->find($id);

        return $pdf->quoteInline($quote)->download('cotizacion_'.$this->safeFilename($quote->number).'.pdf');
    }

    public function send(string $id, AgencyPdfGenerator $pdf): JsonResponse
    {
        /** @var Quote $quote */
        $quote = $this->find($id);
        $client = Client::find($quote->client_id);

        if ($client === null || blank($client->email)) {
            return response()->json(['message' => 'El cliente no tiene un email registrado.'], 422);
        }

        $filename = 'cotizacion_'.$this->safeFilename($quote->number).'.pdf';
        Mail::to($client->email)->send(new AgencyDocumentMail(
            documentType: 'Cotización',
            number: $quote->number,
            clientName: $client->name,
            businessName: $pdf->business()['name'],
            pdfBytes: $pdf->quoteInline($quote)->output(),
            pdfFilename: $filename,
        ));

        return response()->json(['message' => 'Cotización enviada a '.$client->email]);
    }
}
