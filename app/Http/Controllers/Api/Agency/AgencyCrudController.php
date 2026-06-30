<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Http\Resources\Agency\AgencyResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Shared CRUD for the agency entities. Concrete controllers provide the model
 * class and implement store/update (typed FormRequest for auto-validation).
 * Lookups go through find(), which runs inside the request lifecycle so the
 * BelongsToTenant global scope always applies.
 */
abstract class AgencyCrudController extends Controller
{
    /** @return class-string<Model> */
    abstract protected function model(): string;

    public function index(): AnonymousResourceCollection
    {
        $model = $this->model();

        return AgencyResource::collection($model::query()->orderByDesc('created_at')->get());
    }

    public function show(string $id): AgencyResource
    {
        return new AgencyResource($this->find($id));
    }

    public function destroy(string $id): Response
    {
        $this->find($id)->delete();

        return response()->noContent();
    }

    /**
     * Create + refresh so the response carries the full DB row (defaults and
     * unset nullable columns), not just the attributes that were assigned.
     */
    protected function create(array $data): JsonResponse
    {
        $model = $this->model();

        return (new AgencyResource($model::create($data)->refresh()))
            ->response()
            ->setStatusCode(201);
    }

    protected function modify(string $id, array $data): AgencyResource
    {
        $model = $this->find($id);
        $model->update($data);

        return new AgencyResource($model);
    }

    protected function find(string $id): Model
    {
        $model = $this->model();

        return $model::query()->findOrFail($id);
    }
}
