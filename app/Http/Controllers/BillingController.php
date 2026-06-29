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
        if ($request->filled('payment_id')) {
            $mercadoPago->syncPayment((string) $request->query('payment_id'));
        }

        return view('billing.result', [
            'status' => 'success',
            'title' => 'Pago recibido',
            'message' => 'Estamos confirmando tu pago. Si fue aprobado, tu cuenta quedará activa automáticamente.',
        ]);
    }

    public function failure()
    {
        return view('billing.result', [
            'status' => 'failure',
            'title' => 'Pago no completado',
            'message' => 'No se pudo completar el pago. Puedes volver a intentarlo cuando quieras.',
        ]);
    }

    public function pending()
    {
        return view('billing.result', [
            'status' => 'pending',
            'title' => 'Pago pendiente',
            'message' => 'Tu pago quedó pendiente. La cuenta se activará cuando Mercado Pago confirme la aprobación.',
        ]);
    }
}
