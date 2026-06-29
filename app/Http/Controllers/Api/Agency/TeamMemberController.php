<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Requests\Api\Agency\TeamMemberRequest;
use App\Http\Resources\Agency\AgencyResource;
use App\Models\Agency\TeamMember;
use Illuminate\Http\JsonResponse;

class TeamMemberController extends AgencyCrudController
{
    protected function model(): string
    {
        return TeamMember::class;
    }

    public function store(TeamMemberRequest $request): JsonResponse
    {
        return $this->create($request->mapped());
    }

    public function update(TeamMemberRequest $request, string $id): AgencyResource
    {
        return $this->modify($id, $request->mapped());
    }
}
