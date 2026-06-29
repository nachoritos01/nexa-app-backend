<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Requests\Api\Agency\ExpenseRequest;
use App\Http\Resources\Agency\AgencyResource;
use App\Models\Agency\Expense;
use Illuminate\Http\JsonResponse;

class ExpenseController extends AgencyCrudController
{
    protected function model(): string
    {
        return Expense::class;
    }

    public function store(ExpenseRequest $request): JsonResponse
    {
        return $this->create($request->mapped());
    }

    public function update(ExpenseRequest $request, string $id): AgencyResource
    {
        return $this->modify($id, $request->mapped());
    }
}
