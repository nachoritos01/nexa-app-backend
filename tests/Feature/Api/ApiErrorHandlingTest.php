<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiErrorHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('api/_test/boom', function (): void {
            throw new \RuntimeException('Stacktrace-revealing internal detail');
        });

        Route::get('api/_test/missing', function (): void {
            abort(404, 'Some resource detail');
        });
    }

    public function test_api_500_hides_internals_when_debug_off(): void
    {
        config(['app.debug' => false]);

        $response = $this->getJson('api/_test/boom');

        $response->assertStatus(500)
            ->assertExactJson(['message' => 'Ocurrió un error en el servidor. Intenta de nuevo más tarde.']);
        $response->assertDontSee('Stacktrace-revealing internal detail');
    }

    public function test_api_500_keeps_details_when_debug_on(): void
    {
        config(['app.debug' => true]);

        $response = $this->getJson('api/_test/boom');

        $response->assertStatus(500);
        // In debug mode the generic message must NOT replace the real trace.
        $this->assertNotSame(
            'Ocurrió un error en el servidor. Intenta de nuevo más tarde.',
            $response->json('message')
        );
    }

    public function test_4xx_messages_are_not_overridden(): void
    {
        config(['app.debug' => false]);

        $this->getJson('api/_test/missing')
            ->assertStatus(404)
            ->assertJsonMissing(['message' => 'Ocurrió un error en el servidor. Intenta de nuevo más tarde.']);
    }
}
