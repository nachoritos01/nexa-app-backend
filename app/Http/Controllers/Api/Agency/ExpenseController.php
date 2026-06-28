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
        $expense = Expense::create($request->mapped());

        return (new AgencyResource($expense))->response()->setStatusCode(201);
    }

    public function update(ExpenseRequest $request, string $id): AgencyResource
    {
        $expense = $this->find($id);
        $expense->update($request->mapped());

        return new AgencyResource($expense);
    }
}
