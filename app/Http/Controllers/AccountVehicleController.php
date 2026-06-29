<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AccountVehicleController extends Controller
{
    public function store(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('auth.google.redirect');
        }

        $user = Auth::user();

        if (! $user->hasActiveSubscription()) {
            return redirect()->route('billing.show');
        }

        if (! $user->canAddVehicle()) {
            return redirect()
                ->route('account.show')
                ->withErrors(['vehicle_limit' => 'Tu licencia actual no permite agregar más vehículos.']);
        }

        $data = $request->validate([
            'plate' => ['required', 'string', 'max:20'],
            'brand' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'vin' => ['nullable', 'string', 'max:80'],
        ]);

        $vehicle = Vehicle::create([
            'public_token' => (string) Str::uuid(),
            'plate' => Str::upper($data['plate']),
            'brand' => $data['brand'] ?? null,
            'model' => $data['model'] ?? null,
            'year' => $data['year'] ?? null,
            'vin' => $data['vin'] ?? null,
            'status' => 'active',
        ]);

        $user->vehicles()->attach($vehicle, [
            'role' => 'owner',
            'starts_at' => now(),
            'is_primary' => true,
        ]);

        return redirect()
            ->route('account.show')
            ->with('status', 'Vehículo agregado. Ahora puedes subir sus documentos.');
    }
}
