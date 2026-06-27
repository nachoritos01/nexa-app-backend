<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $services = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        $allHealthy = ! in_array('error', $services, true);

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'services' => $services,
            'timestamp' => now()->toISOString(),
        ], $allHealthy ? 200 : 503);
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();

            return 'ok';
        } catch (\Throwable) {
            return 'error';
        }
    }

    private function checkCache(): string
    {
        try {
            Cache::put('health_check', true, 10);
            Cache::forget('health_check');

            return 'ok';
        } catch (\Throwable) {
            return 'error';
        }
    }

    private function checkStorage(): string
    {
        try {
            Storage::disk('local')->put('health_check.txt', 'ok');
            Storage::disk('local')->delete('health_check.txt');

            return 'ok';
        } catch (\Throwable) {
            return 'error';
        }
    }
}
