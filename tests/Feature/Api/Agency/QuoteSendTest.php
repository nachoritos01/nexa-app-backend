<?php

namespace Tests\Feature\Api\Agency;

use App\Mail\Agency\AgencyDocumentMail;
use App\Models\Agency\Client;
use App\Models\Agency\Quote;
use Illuminate\Support\Facades\Mail;

class QuoteSendTest extends AgencyTestCase
{
    private function makeQuote(?string $clientEmail = 'cliente@acme.mx'): Quote
    {
        $client = Client::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'ACME',
            'email' => $clientEmail,
        ]);

        return Quote::create([
            'tenant_id' => $this->tenant->id,
            'number' => 'COT-0001',
            'client_id' => $client->id,
            'date' => '2026-06-01',
            'valid_until' => '2026-06-15',
            'status' => 'sent',
            'items' => [['id' => 'it-0', 'description' => 'Branding', 'quantity' => 1, 'unitPrice' => 45000, 'total' => 45000]],
            'discount' => 0,
            'tax' => 16,
            'subtotal' => 45000,
            'total' => 52200,
        ]);
    }

    public function test_requires_authentication(): void
    {
        $quote = $this->makeQuote();

        $this->postJson("/api/agency/quotes/{$quote->id}/send")->assertUnauthorized();
    }

    public function test_sends_email_with_pdf_attachment(): void
    {
        Mail::fake();
        $quote = $this->makeQuote('cliente@acme.mx');

        $this->withToken($this->token)
            ->postJson("/api/agency/quotes/{$quote->id}/send")
            ->assertOk()
            ->assertJsonPath('message', 'Cotización enviada a cliente@acme.mx');

        Mail::assertSent(AgencyDocumentMail::class, function (AgencyDocumentMail $mail) {
            return $mail->hasTo('cliente@acme.mx')
                && $mail->number === 'COT-0001'
                && $mail->documentType === 'Cotización'
                && count($mail->attachments()) === 1;
        });
    }

    public function test_returns_422_when_client_has_no_email(): void
    {
        Mail::fake();
        $quote = $this->makeQuote(null);

        $this->withToken($this->token)
            ->postJson("/api/agency/quotes/{$quote->id}/send")
            ->assertStatus(422);

        Mail::assertNothingSent();
    }

    public function test_cannot_send_another_tenants_quote(): void
    {
        Mail::fake();
        $quote = $this->makeQuote();

        [, , $otherToken] = $this->makeTenantUser();

        $this->withToken($otherToken)
            ->postJson("/api/agency/quotes/{$quote->id}/send")
            ->assertNotFound();

        Mail::assertNothingSent();
    }
}
