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

    public function test_sync_pending_payments_command_activates_late_approved_payment(): void
    {
        config(['services.mercado_pago.access_token' => 'test-token']);

        $user = User::factory()->create();
        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => 'mercado_pago',
            'provider_payment_id' => 'mp-late-123',
            'status' => 'pending',
            'plan' => 'normal',
            'amount' => 5000,
            'currency' => 'CLP',
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/payments/mp-late-123' => Http::response([
                'id' => 'mp-late-123',
                'status' => 'approved',
                'external_reference' => (string) $payment->id,
            ]),
        ]);

        $this->artisan('vqr:sync-pending-payments')
            ->expectsOutput('Synced 1 Mercado Pago payment(s). Activated 1 license(s).')
            ->assertSuccessful();

        $payment->refresh();

        $this->assertSame('approved', $payment->status);
        $this->assertNotNull($payment->subscription_id);
        $this->assertTrue($user->hasActiveSubscription());
        $this->assertSame(1, $user->activeSubscription()->first()->vehicle_limit);
    }

    public function test_success_return_with_merchant_order_activates_subscription(): void
    {
        config(['services.mercado_pago.access_token' => 'test-token']);

        $user = User::factory()->create();
        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => 'mercado_pago',
            'provider_preference_id' => 'pref-123',
            'status' => 'pending',
            'plan' => 'premium',
            'amount' => 10000,
            'currency' => 'CLP',
        ]);

        Http::fake([
            'https://api.mercadopago.com/merchant_orders/mo-123' => Http::response([
                'id' => 'mo-123',
                'payments' => [
                    ['id' => 'mp-merchant-123'],
                ],
            ]),
            'https://api.mercadopago.com/v1/payments/mp-merchant-123' => Http::response([
                'id' => 'mp-merchant-123',
                'status' => 'approved',
                'external_reference' => (string) $payment->id,
            ]),
        ]);

        $this->get(route('billing.mercado-pago.success', [
            'collection_id' => 'null',
            'payment_id' => 'null',
            'merchant_order_id' => 'mo-123',
            'preference_id' => 'pref-123',
        ]))->assertOk();

        $payment->refresh();

        $this->assertSame('approved', $payment->status);
        $this->assertSame('mp-merchant-123', $payment->provider_payment_id);
        $this->assertNotNull($payment->subscription_id);
        $this->assertTrue($user->hasActiveSubscription());
        $this->assertSame(3, $user->activeSubscription()->first()->vehicle_limit);
    }

    public function test_success_return_redirects_to_account_when_license_is_activated(): void
    {
        config(['services.mercado_pago.access_token' => 'test-token']);

        $user = User::factory()->create();
        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => 'mercado_pago',
            'status' => 'pending',
            'plan' => 'normal',
            'amount' => 5000,
            'currency' => 'CLP',
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/payments/mp-success-123' => Http::response([
                'id' => 'mp-success-123',
                'status' => 'approved',
                'external_reference' => (string) $payment->id,
            ]),
        ]);

        $this->actingAs($user)
            ->get(route('billing.mercado-pago.success', [
                'payment_id' => 'mp-success-123',
            ]))
            ->assertRedirect(route('account.show'));

        $this->assertTrue($user->hasActiveSubscription());
    }

    public function test_sync_pending_payments_command_can_use_preference_id(): void
    {
        config(['services.mercado_pago.access_token' => 'test-token']);

        $user = User::factory()->create();
        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => 'mercado_pago',
            'provider_preference_id' => 'pref-late-123',
            'status' => 'pending',
            'plan' => 'normal',
            'amount' => 5000,
            'currency' => 'CLP',
        ]);

        Http::fake([
            'https://api.mercadopago.com/merchant_orders/search?preference_id=pref-late-123' => Http::response([
                'elements' => [[
                    'id' => 'mo-late-123',
                    'payments' => [
                        ['id' => 'mp-pref-123'],
                    ],
                ]],
            ]),
            'https://api.mercadopago.com/v1/payments/mp-pref-123' => Http::response([
                'id' => 'mp-pref-123',
                'status' => 'approved',
                'external_reference' => (string) $payment->id,
            ]),
        ]);

        $this->artisan('vqr:sync-pending-payments')
            ->expectsOutput('Synced 1 Mercado Pago payment(s). Activated 1 license(s).')
            ->assertSuccessful();

        $this->assertTrue($user->hasActiveSubscription());
        $this->assertSame('approved', $payment->refresh()->status);
    }
}
