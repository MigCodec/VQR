<?php

namespace App\Http\Controllers;

use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request, MercadoPagoService $mercadoPago)
    {
        if (! $this->hasValidSignature($request)) {
            abort(401);
        }

        $paymentId = $request->input('data.id')
            ?? $request->input('id')
            ?? $request->query('data_id')
            ?? $request->query('id');

        $type = $request->input('type') ?? $request->query('topic');

        if ($paymentId && $type === 'merchant_order') {
            $mercadoPago->syncMerchantOrder((string) $paymentId);
        } elseif ($paymentId) {
            $mercadoPago->syncPayment((string) $paymentId);
        }

        return response()->json(['ok' => true]);
    }

    private function hasValidSignature(Request $request): bool
    {
        $secret = config('services.mercado_pago.webhook_secret');

        if (! $secret) {
            if (App::isProduction()) {
                return false;
            }

            Log::warning('Mercado Pago webhook secret is not configured; accepting webhook outside production.');

            return true;
        }

        $signature = $request->header('x-signature');
        $requestId = $request->header('x-request-id');

        if (! $signature || ! $requestId) {
            return false;
        }

        $parts = collect(explode(',', $signature))
            ->mapWithKeys(function (string $part): array {
                [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);

                return [$key => $value];
            });

        $timestamp = $parts->get('ts');
        $hash = $parts->get('v1');
        $dataId = $request->input('data.id') ?? $request->query('data_id');

        if (! $timestamp || ! $hash || ! $dataId) {
            return false;
        }

        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$timestamp};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $hash);
    }
}
