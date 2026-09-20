<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test startowej strony: sprawdza odpowiedź HTTP aplikacji, nie procesy wydarzeń.
 */
class ExampleTest extends TestCase
{
    /**
     * Sprawdza, czy strona startowa zwraca poprawną odpowiedź.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
