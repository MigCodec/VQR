<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function show()
    {
        if (! Auth::check()) {
            return redirect()->route('auth.google.redirect');
        }

        $user = Auth::user()->load(['activeSubscription']);

        if (! $user->hasActiveSubscription() && ! $user->isAdmin()) {
            return redirect()->route('billing.show');
        }

        $accountCard = $user->accountCard();

        $vehicles = $user->activeVehicles()
            ->with(['documents.type'])
            ->get();

        DocumentType::ensureRequiredTypes();

        $documentTypes = DocumentType::query()
            ->where('is_required', true)
            ->orderBy('sort_order')
            ->get();

        return view('account.show', [
            'user' => $user,
            'subscription' => $user->activeSubscription,
            'accountCard' => $accountCard,
            'vehicleCount' => $vehicles->count(),
            'vehicleLimit' => $user->vehicleLimit(),
            'canAddVehicle' => $user->canAddVehicle(),
            'documentTypes' => $documentTypes,
            'vehicles' => $vehicles,
        ]);
    }
}
