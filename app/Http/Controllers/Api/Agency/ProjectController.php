<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Requests\Api\Agency\StoreProjectRequest;
use App\Http\Requests\Api\Agency\UpdateProjectRequest;
use App\Http\Resources\Agency\AgencyResource;
use App\Models\Agency\Project;
use Illuminate\Http\JsonResponse;

class ProjectController extends AgencyCrudController
{
    protected function model(): string
    {
        return Project::class;
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        return $this->create($request->mapped());
    }

    public function update(UpdateProjectRequest $request, string $id): AgencyResource
    {
        return $this->modify($id, $request->mapped());
    }
}
