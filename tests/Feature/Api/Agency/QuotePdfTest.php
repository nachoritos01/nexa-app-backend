<?php

namespace Tests\Feature\Api\Agency;

use App\Models\Agency\Client;
use App\Models\Agency\Quote;

class QuotePdfTest extends AgencyTestCase
{
    /** Creates a quote for the current tenant (BelongsToTenant::creating forces tenant_id). */
    private function makeQuote(): Quote
    {
        $client = Client::create(['tenant_id' => $this->tenant->id, 'name' => 'ACME']);

        return Quote::create([
            'tenant_id' => $this->tenant->id,
            'number' => 'COT-0001',
            'client_id' => $client->id,
            'date' => '2026-06-01',
            'valid_until' => '2026-06-15',
            'status' => 'sent',
            'items' => [
                ['id' => 'it-0', 'description' => 'Branding', 'quantity' => 1, 'unitPrice' => 45000, 'total' => 45000],
            ],
            'discount' => 5,
            'tax' => 16,
            'subtotal' => 45000,
            'total' => 49590,
            'terms' => 'Pago 50/50.',
        ]);
    }

    public function test_requires_authentication(): void
    {
        $quote = $this->makeQuote();

        $this->getJson("/api/agency/quotes/{$quote->id}/pdf")->assertUnauthorized();
    }

    public function test_downloads_pdf_for_own_quote(): void
    {
        $quote = $this->makeQuote();

        $response = $this->withToken($this->token)->get("/api/agency/quotes/{$quote->id}/pdf");

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_cannot_download_another_tenants_quote(): void
    {
        // Victim belongs to the current tenant; request as a different tenant's
        // token — api.tenant rebinds currentTenant during the request, so the
        // global scope hides the row → 404. (One identity per request: switching
        // tokens mid-test hits the auth guard's per-request user cache.)
        $quote = $this->makeQuote();

        [, , $otherToken] = $this->makeTenantUser();

        $this->withToken($otherToken)
            ->getJson("/api/agency/quotes/{$quote->id}/pdf")
            ->assertNotFound();
    }
}
