<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Requests\Api\Agency\QuoteRequest;
use App\Http\Resources\Agency\AgencyResource;
use App\Models\Agency\Quote;
use Illuminate\Http\JsonResponse;

class QuoteController extends AgencyCrudController
{
    protected function model(): string
    {
        return Quote::class;
    }

    public function store(QuoteRequest $request): JsonResponse
    {
        $quote = Quote::create($request->mapped());

        return (new AgencyResource($quote))->response()->setStatusCode(201);
    }

    public function update(QuoteRequest $request, string $id): AgencyResource
    {
        $quote = $this->find($id);
        $quote->update($request->mapped());

        return new AgencyResource($quote);
    }
}
