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

    public function show(Client $client): ClientResource
    {
        return new ClientResource($client);
    }

    public function update(UpdateClientRequest $request, Client $client): ClientResource
    {
        $client->update($request->mapped());

        return new ClientResource($client);
    }

    public function destroy(Client $client): Response
    {
        $client->delete();

        return response()->noContent();
    }
}
