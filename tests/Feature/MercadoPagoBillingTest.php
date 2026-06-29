<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_preference_and_redirects_to_mercado_pago(): void
    {
        config(['services.mercado_pago.access_token' => 'test-token']);

        $user = User::factory()->create([
            'email' => 'pagador@vqr.test',
        ]);

        Http::fake([
            'https://api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-123',
                'init_point' => 'https://www.mercadopago.com/checkout/v1/redirect?pref_id=pref-123',
            ]),
        ]);

        $this->actingAs($user)
            ->post(route('billing.checkout'), [
                'plan' => 'normal',
            ])
            ->assertRedirect('https://www.mercadopago.com/checkout/v1/redirect?pref_id=pref-123');

        $payment = Payment::firstOrFail();

        $this->assertSame($user->id, $payment->user_id);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('normal', $payment->plan);
        $this->assertSame('pref-123', $payment->provider_preference_id);
    }

    public function test_webhook_approved_payment_activates_subscription(): void
    {
        config([
            'services.mercado_pago.access_token' => 'test-token',
            'services.mercado_pago.webhook_secret' => 'webhook-secret',
        ]);

        $user = User::factory()->create();
        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => 'mercado_pago',
            'status' => 'pending',
            'plan' => 'premium',
            'amount' => 10000,
            'currency' => 'CLP',
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/payments/mp-123' => Http::response([
                'id' => 'mp-123',
                'status' => 'approved',
                'external_reference' => (string) $payment->id,
            ]),
        ]);

        $timestamp = '1710000000';
        $requestId = 'request-123';
        $manifest = "id:mp-123;request-id:{$requestId};ts:{$timestamp};";
        $signature = 'ts='.$timestamp.',v1='.hash_hmac('sha256', $manifest, 'webhook-secret');

        $this->withHeaders([
            'x-request-id' => $requestId,
            'x-signature' => $signature,
        ])->postJson(route('webhooks.mercado-pago'), [
            'type' => 'payment',
            'data' => [
                'id' => 'mp-123',
            ],
        ])->assertOk();

        $payment->refresh();

        $this->assertSame('approved', $payment->status);
        $this->assertSame('mp-123', $payment->provider_payment_id);
        $this->assertNotNull($payment->subscription_id);
        $this->assertTrue($user->hasActiveSubscription());
        $this->assertSame(3, $user->activeSubscription()->first()->vehicle_limit);
    }
}
