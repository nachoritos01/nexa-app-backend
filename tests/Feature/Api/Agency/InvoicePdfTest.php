<?php

namespace Tests\Feature\Api\Agency;

use App\Models\Agency\Client;
use App\Models\Agency\Invoice;

class InvoicePdfTest extends AgencyTestCase
{
    /** Creates an invoice for the current tenant (BelongsToTenant::creating forces tenant_id). */
    private function makeInvoice(string $number = 'FAC-0001'): Invoice
    {
        $client = Client::create(['tenant_id' => $this->tenant->id, 'name' => 'ACME']);

        return Invoice::create([
            'tenant_id' => $this->tenant->id,
            'number' => $number,
            'client_id' => $client->id,
            'date' => '2026-06-01',
            'due_date' => '2026-06-30',
            'status' => 'sent',
            'items' => [
                ['id' => 'it-0', 'description' => 'Portal 2.0', 'quantity' => 1, 'unitPrice' => 90000, 'total' => 90000],
            ],
            'subtotal' => 90000,
            'tax' => 16,
            'total' => 104400,
        ]);
    }

    public function test_requires_authentication(): void
    {
        $invoice = $this->makeInvoice();

        $this->getJson("/api/agency/invoices/{$invoice->id}/pdf")->assertUnauthorized();
    }

    public function test_downloads_pdf_for_own_invoice(): void
    {
        $invoice = $this->makeInvoice();

        $response = $this->withToken($this->token)->get("/api/agency/invoices/{$invoice->id}/pdf");

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_downloads_pdf_when_number_contains_slash(): void
    {
        // Slash-style numbers (e.g. FAC/2026/12) must not break Content-Disposition
        // (HeaderUtils::makeDisposition throws on "/" in the filename → 500).
        $invoice = $this->makeInvoice('FAC/2026/12');

        $response = $this->withToken($this->token)->get("/api/agency/invoices/{$invoice->id}/pdf");

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('factura_FAC-2026-12.pdf', $response->headers->get('content-disposition'));
    }

    public function test_cannot_download_another_tenants_invoice(): void
    {
        $invoice = $this->makeInvoice();

        [, , $otherToken] = $this->makeTenantUser();

        $this->withToken($otherToken)
            ->getJson("/api/agency/invoices/{$invoice->id}/pdf")
            ->assertNotFound();
    }
}
