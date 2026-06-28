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
        $member = TeamMember::create($request->mapped());

        return (new AgencyResource($member))->response()->setStatusCode(201);
    }

    public function update(TeamMemberRequest $request, string $id): AgencyResource
    {
        $member = $this->find($id);
        $member->update($request->mapped());

        return new AgencyResource($member);
    }
}
