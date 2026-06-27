<?php

namespace App\Services;

use App\Constants\BusinessRules;
use App\Models\FeatureUsage;
use App\Models\Order;
use App\Models\Quote;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfGenerator
{
    /**
     * Generate PDF for a quote
     */
    public function generateQuote(Quote $quote): string
    {
        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'business' => $this->getBusinessInfo(currentTenant()),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        $filename = "quotes/quote_{$quote->id}_".now()->format('Ymd_His').'.pdf';
        $path = storage_path('app/public/'.$filename);

        // Ensure directory exists
        if (! file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $pdf->save($path);

        FeatureUsage::track('pdf_generated');

        return $filename;
    }

    /**
     * Get business configuration, with tenant overrides
     */
    public function getBusinessInfo(?Tenant $tenant = null): array
    {
        $settings = $tenant !== null ? ($tenant->settings ?? []) : [];
        $tenantName = $tenant !== null ? $tenant->name : null;

        return [
            'name' => $settings['business_name'] ?? $tenantName ?? config('business.name'),
            'slogan' => $settings['slogan'] ?? config('business.slogan'),
            'phone' => $settings['phone'] ?? config('business.contact.phone'),
            'email' => $settings['email'] ?? config('business.contact.email'),
            'address' => $settings['address'] ?? $settings['city'] ?? '',
            'logo_path' => $settings['logo_path'] ?? null,
            'terms' => [
                'Quote valid for '.BusinessRules::quoteExpiryDays().' days',
                'Prices in '.BusinessRules::currency(),
            ],
        ];
    }

    /**
     * Generate inline PDF (for download without saving)
     */
    public function generateQuoteInline(Quote $quote): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'business' => $this->getBusinessInfo(currentTenant()),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);
    }

    /**
     * Generate inline PDF for an order (for download without saving)
     */
    public function generateOrderInline(Order $order): \Barryvdh\DomPDF\PDF
    {
        $order->load(['lines.item', 'payments', 'location']);

        return Pdf::loadView('pdf.order', [
            'order' => $order,
            'business' => $this->getBusinessInfo(currentTenant()),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);
    }
}
