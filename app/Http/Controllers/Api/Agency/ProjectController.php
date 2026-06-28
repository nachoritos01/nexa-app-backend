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
        $project = Project::create($request->mapped());

        return (new AgencyResource($project))->response()->setStatusCode(201);
    }

    public function update(UpdateProjectRequest $request, string $id): AgencyResource
    {
        $project = $this->find($id);
        $project->update($request->mapped());

        return new AgencyResource($project);
    }
}
