<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_uses_socialite_provider(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('scopes')
            ->once()
            ->with(['openid', 'profile', 'email'])
            ->andReturnSelf();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(new RedirectResponse('https://accounts.google.com/oauth'));

        $socialite = Mockery::mock(SocialiteFactory::class);
        $socialite->shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->app->instance(SocialiteFactory::class, $socialite);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect('https://accounts.google.com/oauth');
    }

    public function test_google_callback_creates_user_and_logs_in(): void
    {
        $this->mockGoogleUser(
            id: 'google-123',
            name: 'Usuario Google',
            email: 'google@vqr.test',
            avatar: 'https://example.com/avatar.jpg',
        );

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('billing.show'));

        $user = User::where('email', 'google@vqr.test')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertSame('Usuario Google', $user->name);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_google_callback_links_existing_verified_user_by_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existente@vqr.test',
            'email_verified_at' => now(),
            'google_id' => null,
        ]);

        $this->mockGoogleUser(
            id: 'google-existing',
            name: 'Nombre Google',
            email: 'existente@vqr.test',
            avatar: 'https://example.com/avatar-existing.jpg',
        );

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('billing.show'));

        $existingUser->refresh();

        $this->assertAuthenticatedAs($existingUser);
        $this->assertSame('google-existing', $existingUser->google_id);
        $this->assertSame('https://example.com/avatar-existing.jpg', $existingUser->avatar_url);
        $this->assertSame(1, User::where('email', 'existente@vqr.test')->count());
    }

    public function test_google_callback_verifies_and_links_existing_unverified_user_by_email(): void
    {
        $existingUser = User::factory()->unverified()->create([
            'email' => 'sinverificar@vqr.test',
            'google_id' => null,
        ]);

        $this->mockGoogleUser(
            id: 'google-unverified',
            name: 'Cuenta Verificada Google',
            email: 'sinverificar@vqr.test',
            avatar: 'https://example.com/avatar-unverified.jpg',
        );

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('billing.show'));

        $existingUser->refresh();

        $this->assertAuthenticatedAs($existingUser);
        $this->assertSame('google-unverified', $existingUser->google_id);
        $this->assertNotNull($existingUser->email_verified_at);
        $this->assertSame(1, User::where('email', 'sinverificar@vqr.test')->count());
    }

    public function test_google_callback_redirects_active_user_to_account(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'activo@vqr.test',
            'email_verified_at' => now(),
            'google_id' => 'google-active',
        ]);

        Subscription::create([
            'user_id' => $existingUser->id,
            'status' => 'active',
            'amount' => 4990,
            'currency' => 'CLP',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
        ]);

        $this->mockGoogleUser(
            id: 'google-active',
            name: 'Usuario Activo',
            email: 'activo@vqr.test',
            avatar: 'https://example.com/avatar-active.jpg',
        );

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('account.show'));

        $this->assertAuthenticatedAs($existingUser);
    }

    private function mockGoogleUser(string $id, string $name, string $email, string $avatar): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')
            ->once()
            ->andReturn((new SocialiteUser)->setRaw([
                'sub' => $id,
            ])->map([
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'avatar' => $avatar,
            ]));

        $socialite = Mockery::mock(SocialiteFactory::class);
        $socialite->shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->app->instance(SocialiteFactory::class, $socialite);
    }
}
