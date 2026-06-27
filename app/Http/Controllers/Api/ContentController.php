<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    private array $validTypes = ['faq', 'policies', 'sales-scripts', 'terms'];

    /**
     * GET /api/content/{type}
     * Get markdown content by type
     */
    public function show(string $type): JsonResponse
    {
        if (! in_array($type, $this->validTypes)) {
            return response()->json([
                'error' => 'Tipo de contenido no válido',
                'valid_types' => $this->validTypes,
            ], 404);
        }

        $path = resource_path("markdown/{$type}.md");

        if (! file_exists($path)) {
            return response()->json([
                'error' => 'Contenido no encontrado',
                'type' => $type,
            ], 404);
        }

        $content = file_get_contents($path);

        return response()->json([
            'type' => $type,
            'content' => $content,
            'html' => Str::markdown($content),
            'updatedAt' => date('Y-m-d H:i:s', filemtime($path)),
        ]);
    }

    /**
     * GET /api/content
     * List available content types
     */
    public function index(): JsonResponse
    {
        $available = [];

        foreach ($this->validTypes as $type) {
            $path = resource_path("markdown/{$type}.md");
            $available[] = [
                'type' => $type,
                'exists' => file_exists($path),
                'url' => "/api/content/{$type}",
            ];
        }

        return response()->json([
            'data' => $available,
        ]);
    }
}
