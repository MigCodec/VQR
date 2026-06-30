<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Documentos del vehículo')
            ->assertSee('summary_large_image');
    }

    public function test_authenticated_user_is_redirected_from_landing_to_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('landing'))
            ->assertRedirect(route('account.show'));
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('landing'));

        $this->assertGuest();
    }
}
