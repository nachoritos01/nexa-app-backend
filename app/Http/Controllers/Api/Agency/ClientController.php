<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agency\StoreClientRequest;
use App\Http\Requests\Api\Agency\UpdateClientRequest;
use App\Http\Resources\Agency\ClientResource;
use App\Models\Agency\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClientController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ClientResource::collection(
            Client::query()->orderBy('name')->get()
        );
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = Client::create($request->mapped());

        return (new ClientResource($client))->response()->setStatusCode(201);
    }

    public function show(string $client): ClientResource
    {
        return new ClientResource($this->findScoped($client));
    }

    public function update(UpdateClientRequest $request, string $client): ClientResource
    {
        $model = $this->findScoped($client);
        $model->update($request->mapped());

        return new ClientResource($model);
    }

    public function destroy(string $client): Response
    {
        $this->findScoped($client)->delete();

        return response()->noContent();
    }

    /**
     * Resolve a client by id inside the request lifecycle, where the tenant is
     * already set, so the BelongsToTenant global scope always applies. This avoids
     * relying on route-model binding running after the tenant middleware.
     */
    private function findScoped(string $id): Client
    {
        return Client::query()->findOrFail($id);
    }
}
