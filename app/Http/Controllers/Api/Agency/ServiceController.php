<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Requests\Api\Agency\ServiceRequest;
use App\Http\Resources\Agency\AgencyResource;
use App\Models\Agency\Service;
use Illuminate\Http\JsonResponse;

class ServiceController extends AgencyCrudController
{
    protected function model(): string
    {
        return Service::class;
    }

    public function store(ServiceRequest $request): JsonResponse
    {
        $service = Service::create($request->mapped());

        return (new AgencyResource($service))->response()->setStatusCode(201);
    }

    public function update(ServiceRequest $request, string $id): AgencyResource
    {
        $service = $this->find($id);
        $service->update($request->mapped());

        return new AgencyResource($service);
    }
}
