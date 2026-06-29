<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agency\SettingsRequest;
use App\Models\Agency\AgencySetting;
use Illuminate\Http\JsonResponse;

/**
 * Per-tenant settings singleton. Stored as a JSON blob in the panel's shape,
 * so the panel can read/merge it directly against its defaults.
 */
class SettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $setting = AgencySetting::query()->first();
        $data = $setting !== null && $setting->data !== null ? $setting->data : (object) [];

        return response()->json(['data' => $data]);
    }

    public function update(SettingsRequest $request): JsonResponse
    {
        // createOrFirst is race-safe: on a concurrent insert it catches the
        // unique(tenant_id) violation and re-selects instead of 500-ing.
        // tenant_id is set by the BelongsToTenant trait + global scope.
        $setting = AgencySetting::createOrFirst([]);
        $setting->data = array_merge($setting->data ?? [], $request->validated());
        $setting->save();

        return response()->json(['data' => $setting->data]);
    }
}
