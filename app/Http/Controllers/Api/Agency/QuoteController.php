<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Requests\Api\Agency\QuoteRequest;
use App\Http\Resources\Agency\AgencyResource;
use App\Models\Agency\Quote;
use App\Services\Agency\AgencyPdfGenerator;
use Illuminate\Http\JsonResponse;
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
}
