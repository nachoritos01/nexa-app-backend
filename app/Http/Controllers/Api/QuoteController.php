<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Services\PdfGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QuoteController extends Controller
{
    public function __construct(
        private PdfGenerator $pdfGenerator
    ) {
    }

    /**
     * POST /api/quotes
     * Create a new quote
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'customer_name' => 'sometimes|string|max:255',
            'customer_phone' => 'sometimes|string|max:20',
            'notes' => 'sometimes|string',
        ]);

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }

        $quote = Quote::create([
            'customer_name' => $validated['customer_name'] ?? null,
            'customer_phone' => $validated['customer_phone'] ?? null,
            'items' => $validated['items'],
            'subtotal' => $subtotal,
            'notes' => $validated['notes'] ?? null,
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'data' => $quote,
            'message' => 'Cotización creada exitosamente',
        ], 201);
    }

    /**
     * GET /api/quotes/{quote}/pdf
     * Download quote as PDF
     */
    public function downloadPdf(Quote $quote): Response
    {
        $pdf = $this->pdfGenerator->generateQuoteInline($quote);

        $filename = "cotizacion_{$quote->id}.pdf";

        return $pdf->download($filename);
    }

    /**
     * POST /api/quotes/{quote}/generate-pdf
     * Generate and save PDF, return path
     */
    public function generatePdf(Quote $quote): JsonResponse
    {
        $pdfPath = $this->pdfGenerator->generateQuote($quote);

        $quote->update(['pdf_path' => $pdfPath]);

        return response()->json([
            'data' => $quote->fresh(),
            'pdf_url' => asset('storage/'.$pdfPath),
            'message' => 'PDF generado exitosamente',
        ]);
    }
}
