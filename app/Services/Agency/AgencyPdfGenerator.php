<?php

namespace App\Services\Agency;

use App\Models\Agency\Client;
use App\Models\Agency\Invoice;
use App\Models\Agency\Quote;
use App\Services\PdfGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;

/**
 * PDF generation for the agency domain (quotes, invoices).
 *
 * Separate from the e-commerce PdfGenerator (which is typed to App\Models\Quote/Order
 * and its own blade shape). Reuses that service only for tenant branding via the
 * public getBusinessInfo(). Client lookup goes through the tenant-scoped model, so
 * the BelongsToTenant global scope always applies.
 */
class AgencyPdfGenerator
{
    public function __construct(
        private PdfGenerator $pdf
    ) {
    }

    public function quoteInline(Quote $quote): DomPDF
    {
        return Pdf::loadView('pdf.agency-quote', [
            'quote' => $quote,
            'client' => Client::find($quote->client_id),
            'business' => $this->pdf->getBusinessInfo(currentTenant()),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);
    }

    public function invoiceInline(Invoice $invoice): DomPDF
    {
        return Pdf::loadView('pdf.agency-invoice', [
            'invoice' => $invoice,
            'client' => Client::find($invoice->client_id),
            'business' => $this->pdf->getBusinessInfo(currentTenant()),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);
    }
}
