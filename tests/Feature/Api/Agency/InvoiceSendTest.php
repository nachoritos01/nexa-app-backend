<?php

namespace Tests\Feature\Api\Agency;

use App\Mail\Agency\AgencyDocumentMail;
use App\Models\Agency\Client;
use App\Models\Agency\Invoice;
use Illuminate\Support\Facades\Mail;

class InvoiceSendTest extends AgencyTestCase
{
    private function makeInvoice(?string $clientEmail = 'cliente@acme.mx'): Invoice
    {
        $client = Client::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'ACME',
            'email' => $clientEmail,
        ]);

        return Invoice::create([
            'tenant_id' => $this->tenant->id,
            'number' => 'FAC-0001',
            'client_id' => $client->id,
            'date' => '2026-06-01',
            'due_date' => '2026-06-30',
            'status' => 'sent',
            'items' => [['id' => 'it-0', 'description' => 'Portal 2.0', 'quantity' => 1, 'unitPrice' => 90000, 'total' => 90000]],
            'subtotal' => 90000,
            'tax' => 16,
            'total' => 104400,
        ]);
    }

    public function test_requires_authentication(): void
    {
        $invoice = $this->makeInvoice();

        $this->postJson("/api/agency/invoices/{$invoice->id}/send")->assertUnauthorized();
    }

    public function test_sends_email_with_pdf_attachment(): void
    {
        Mail::fake();
        $invoice = $this->makeInvoice('cliente@acme.mx');

        $this->withToken($this->token)
            ->postJson("/api/agency/invoices/{$invoice->id}/send")
            ->assertOk()
            ->assertJsonPath('message', 'Factura enviada a cliente@acme.mx');

        Mail::assertSent(AgencyDocumentMail::class, function (AgencyDocumentMail $mail) {
            return $mail->hasTo('cliente@acme.mx')
                && $mail->number === 'FAC-0001'
                && $mail->documentType === 'Factura'
                && count($mail->attachments()) === 1;
        });
    }

    public function test_returns_422_when_client_has_no_email(): void
    {
        Mail::fake();
        $invoice = $this->makeInvoice(null);

        $this->withToken($this->token)
            ->postJson("/api/agency/invoices/{$invoice->id}/send")
            ->assertStatus(422);

        Mail::assertNothingSent();
    }

    public function test_cannot_send_another_tenants_invoice(): void
    {
        Mail::fake();
        $invoice = $this->makeInvoice();

        [, , $otherToken] = $this->makeTenantUser();

        $this->withToken($otherToken)
            ->postJson("/api/agency/invoices/{$invoice->id}/send")
            ->assertNotFound();

        Mail::assertNothingSent();
    }
}
