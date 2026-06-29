<?php

namespace App\Http\Controllers;

use App\Models\Card;

class PublicCardController extends Controller
{
    public function show(string $shortCode)
    {
        $card = Card::query()
            ->where('short_code', $shortCode)
            ->where('status', 'active')
            ->whereNotNull('user_id')
            ->with([
                'user.activeSubscription',
            ])
            ->firstOrFail();

        if (! $card->user->hasActiveSubscription()) {
            return response()->view('public.subscription-expired', [
                'card' => $card,
            ], 402);
        }

        $vehicles = $card->user
            ->activeVehicles()
            ->with('documents.type')
            ->get();

        return view('public.card', [
            'card' => $card,
            'vehicles' => $vehicles,
        ]);
    }
}
