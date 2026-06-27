<?php

namespace Tests\Feature;

use App\Models\CancellationSurvey;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationSurveyTest extends TestCase
{
    use RefreshDatabase;

    public function test_survey_stored_correctly(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['owner_id' => $user->id]);

        $survey = CancellationSurvey::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'reason' => 'price',
            'details' => 'Too expensive for my business',
        ]);

        $this->assertDatabaseHas('cancellation_surveys', [
            'tenant_id' => $tenant->id,
            'reason' => 'price',
            'details' => 'Too expensive for my business',
        ]);

        $this->assertEquals($tenant->id, $survey->tenant->id);
        $this->assertEquals($user->id, $survey->user->id);
    }

    public function test_reason_labels_returns_five_options(): void
    {
        $labels = CancellationSurvey::reasonLabels();

        $this->assertCount(5, $labels);
        $this->assertArrayHasKey('price', $labels);
        $this->assertArrayHasKey('missing_features', $labels);
        $this->assertArrayHasKey('closed_business', $labels);
        $this->assertArrayHasKey('competitor', $labels);
        $this->assertArrayHasKey('other', $labels);
    }
}
