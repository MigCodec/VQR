<?php

namespace App\Http\Controllers;

use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    public function show()
    {
        return view('billing.show');
    }

    public function checkout(MercadoPagoService $mercadoPago)
    {
        if (! Auth::check()) {
            return redirect()->route('auth.google.redirect');
        }

        $plan = request()->validate([
            'plan' => ['required', 'in:normal,premium'],
        ])['plan'];

        return redirect()->away($mercadoPago->createCheckout(Auth::user(), $plan));
    }

    public function success(Request $request, MercadoPagoService $mercadoPago)
    {
        $paymentId = $this->validMercadoPagoId($request->query('payment_id'))
            ?? $this->validMercadoPagoId($request->query('collection_id'));
        $merchantOrderId = $this->validMercadoPagoId($request->query('merchant_order_id'));
        $preferenceId = $this->validMercadoPagoId($request->query('preference_id'));

        if ($paymentId) {
            $mercadoPago->syncPayment($paymentId);
        } elseif ($merchantOrderId) {
            $mercadoPago->syncMerchantOrder($merchantOrderId);
        } elseif ($preferenceId) {
            $mercadoPago->syncPreferencePayments($preferenceId);
        }

        if (Auth::check() && Auth::user()->fresh()->hasActiveSubscription()) {
            return redirect()
                ->route('account.show')
                ->with('status', 'Pago confirmado. Tu licencia VQR ya esta activa.');
        }

        return view('billing.result', [
            'status' => 'success',
            'title' => 'Pago recibido',
            'message' => 'Estamos confirmando tu pago. Si fue aprobado, tu cuenta quedara activa automaticamente.',
            'actionRoute' => route('account.show'),
            'actionLabel' => 'Ir a mi cuenta',
        ]);
    }

    public function failure()
    {
        return view('billing.result', [
            'status' => 'failure',
            'title' => 'Pago no completado',
            'message' => 'No se pudo completar el pago. Puedes volver a intentarlo cuando quieras.',
            'actionRoute' => route('billing.show'),
            'actionLabel' => 'Volver a planes',
        ]);
    }

    public function pending()
    {
        return view('billing.result', [
            'status' => 'pending',
            'title' => 'Pago pendiente',
            'message' => 'Tu pago quedo pendiente. La cuenta se activara cuando Mercado Pago confirme la aprobacion.',
            'actionRoute' => route('account.show'),
            'actionLabel' => 'Ir a mi cuenta',
        ]);
    }

    private function validMercadoPagoId(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || strtolower($value) === 'null') {
            return null;
        }

        return $value;
    }
}
