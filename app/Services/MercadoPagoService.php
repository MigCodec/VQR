<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MercadoPagoService
{
    private const API_BASE = 'https://api.mercadopago.com';

    public function createCheckout(User $user, string $plan = 'normal'): string
    {
        $accessToken = config('services.mercado_pago.access_token');

        if (! $accessToken) {
            throw new RuntimeException('Mercado Pago no tiene access token configurado.');
        }

        $plan = array_key_exists($plan, Subscription::PLANS) ? $plan : 'normal';
        $planConfig = Subscription::plan($plan);

        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => 'mercado_pago',
            'status' => 'pending',
            'plan' => $plan,
            'amount' => $planConfig['amount'],
            'currency' => 'CLP',
        ]);

        $response = $this->client()
            ->post('/checkout/preferences', [
                'items' => [[
                    'id' => "vqr-annual-subscription-{$plan}",
                    'title' => "Suscripción anual VQR {$planConfig['label']}",
                    'description' => "Activación anual VQR para {$planConfig['vehicle_limit']} vehículo(s)",
                    'quantity' => 1,
                    'currency_id' => 'CLP',
                    'unit_price' => $planConfig['amount'],
                ]],
                'payer' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'back_urls' => [
                    'success' => route('billing.mercado-pago.success'),
                    'failure' => route('billing.mercado-pago.failure'),
                    'pending' => route('billing.mercado-pago.pending'),
                ],
                'auto_return' => 'approved',
                'external_reference' => (string) $payment->id,
                'notification_url' => route('webhooks.mercado-pago'),
                'metadata' => [
                    'payment_id' => $payment->id,
                    'user_id' => $user->id,
                    'plan' => $plan,
                ],
            ]);

        if (! $response->successful()) {
            $payment->update([
                'status' => 'preference_failed',
                'raw_payload' => $response->json(),
            ]);

            throw new RuntimeException('Mercado Pago no pudo crear la preferencia de pago.');
        }

        $payload = $response->json();

        $payment->update([
            'provider_preference_id' => $payload['id'] ?? null,
            'raw_payload' => $payload,
        ]);

        return $payload['init_point'] ?? $payload['sandbox_init_point'] ?? throw new RuntimeException('Mercado Pago no entregó URL de checkout.');
    }

    public function syncPayment(string $providerPaymentId): ?Payment
    {
        $response = $this->client()->get("/v1/payments/{$providerPaymentId}");

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();
        $externalReference = $payload['external_reference'] ?? null;

        $payment = Payment::query()
            ->when($externalReference, fn ($query) => $query->whereKey($externalReference))
            ->first();

        if (! $payment) {
            return null;
        }

        DB::transaction(function () use ($payment, $payload, $providerPaymentId): void {
            $payment->update([
                'provider_payment_id' => $providerPaymentId,
                'status' => $payload['status'] ?? 'unknown',
                'paid_at' => ($payload['status'] ?? null) === 'approved' ? now() : null,
                'raw_payload' => $payload,
            ]);

            if (($payload['status'] ?? null) === 'approved') {
                $subscription = $this->activateSubscription($payment);

                $payment->update([
                    'subscription_id' => $subscription->id,
                ]);
            }
        });

        return $payment->fresh();
    }

    public function activateSubscription(Payment $payment): Subscription
    {
        $existing = Subscription::query()
            ->where('user_id', $payment->user_id)
            ->latest('expires_at')
            ->first();

        $startsAt = now();
        $baseDate = $existing?->isActive() ? $existing->expires_at : $startsAt;
        $expiresAt = Carbon::parse($baseDate)->addYear();
        $planConfig = Subscription::plan($payment->plan);

        return Subscription::updateOrCreate([
            'user_id' => $payment->user_id,
        ], [
            'status' => 'active',
            'plan' => $payment->plan,
            'vehicle_limit' => $planConfig['vehicle_limit'],
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'last_payment_id' => $payment->id,
        ]);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(self::API_BASE)
            ->acceptJson()
            ->asJson()
            ->withToken(config('services.mercado_pago.access_token'));
    }
}
