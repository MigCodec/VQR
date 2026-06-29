<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrantLicenseCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_grants_one_year_license_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'licencia@vqr.test',
        ]);

        $this->artisan('vqr:grant-license', [
            'user' => $user->email,
        ])->assertSuccessful();

        $subscription = Subscription::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('active', $subscription->status);
        $this->assertSame('normal', $subscription->plan);
        $this->assertSame(1, $subscription->vehicle_limit);
        $this->assertTrue($subscription->expires_at->isAfter(now()->addMonths(11)));
        $this->assertTrue($user->hasActiveSubscription());
    }

    public function test_extends_active_license_from_current_expiration(): void
    {
        $user = User::factory()->create();
        $currentExpiration = now()->addMonths(6);

        Subscription::create([
            'user_id' => $user->id,
            'status' => 'active',
            'amount' => 5000,
            'currency' => 'CLP',
            'starts_at' => now()->subMonth(),
            'expires_at' => $currentExpiration,
        ]);

        $this->artisan('vqr:grant-license', [
            'user' => (string) $user->id,
        ])->assertSuccessful();

        $subscription = Subscription::where('user_id', $user->id)->firstOrFail();

        $this->assertTrue($subscription->expires_at->isSameDay($currentExpiration->copy()->addYear()));
    }

    public function test_returns_failure_for_missing_user(): void
    {
        $this->artisan('vqr:grant-license', [
            'user' => 'missing@vqr.test',
        ])->assertFailed();
    }

    public function test_grants_premium_license(): void
    {
        $user = User::factory()->create();

        $this->artisan('vqr:grant-license', [
            'user' => (string) $user->id,
            '--plan' => 'premium',
        ])->assertSuccessful();

        $subscription = Subscription::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('premium', $subscription->plan);
        $this->assertSame(3, $subscription->vehicle_limit);
        $this->assertSame(10000, $subscription->amount);
    }

    public function test_grants_and_revokes_admin_permission_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@vqr.test',
        ]);

        $this->artisan('vqr:grant-admin', [
            'email' => $user->email,
        ])->assertSuccessful();

        $this->assertTrue($user->refresh()->is_admin);

        $this->artisan('vqr:grant-admin', [
            'email' => $user->email,
            '--revoke' => true,
        ])->assertSuccessful();

        $this->assertFalse($user->refresh()->is_admin);
    }
}
