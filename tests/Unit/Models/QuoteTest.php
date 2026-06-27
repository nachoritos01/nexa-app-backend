<?php

namespace Tests\Unit\Models;

use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    use RefreshDatabase;
    use WithTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
    }

    public function test_is_expired_returns_true_for_past_date(): void
    {
        $quote = Quote::factory()->expired()->create();

        $this->assertTrue($quote->is_expired);
    }

    public function test_is_expired_returns_false_for_future_date(): void
    {
        $quote = Quote::factory()->create([
            'expires_at' => now()->addDays(7),
        ]);

        $this->assertFalse($quote->is_expired);
    }

    public function test_is_expired_returns_false_for_null_date(): void
    {
        $quote = Quote::factory()->create([
            'expires_at' => null,
        ]);

        $this->assertFalse($quote->is_expired);
    }

    public function test_is_accepted_returns_true_when_accepted(): void
    {
        $quote = Quote::factory()->accepted()->create();

        $this->assertTrue($quote->is_accepted);
    }

    public function test_is_accepted_returns_false_when_not_accepted(): void
    {
        $quote = Quote::factory()->create();

        $this->assertFalse($quote->is_accepted);
    }
}
