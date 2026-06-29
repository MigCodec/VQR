<?php

namespace App\Http\Controllers\Admin;

use App\Models\Card;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminCardController extends AdminController
{
    public function store(Request $request)
    {
        if ($response = $this->denyUnlessAdmin()) {
            return $response;
        }

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Card::TYPES))],
            'user_id' => ['nullable', 'exists:users,id'],
            'label' => ['nullable', 'string', 'max:120'],
            'nfc_identifier' => ['nullable', 'string', 'max:160', 'unique:cards,nfc_identifier'],
        ]);

        Card::create([
            'user_id' => $data['user_id'] ?? null,
            'type' => $data['type'],
            'nfc_identifier' => $data['nfc_identifier'] ?: $this->defaultNfcIdentifier($data['type']),
            'short_code' => Str::lower(Str::random(8)),
            'label' => $data['label'] ?: Card::TYPES[$data['type']],
            'status' => 'active',
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Tarjeta creada.');
    }

    public function attach(Request $request, Card $card)
    {
        if ($response = $this->denyUnlessAdmin()) {
            return $response;
        }

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $user = User::findOrFail($data['user_id']);

        $card->update([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('status', "Tarjeta vinculada a {$user->email}.");
    }

    public function detach(Card $card)
    {
        if ($response = $this->denyUnlessAdmin()) {
            return $response;
        }

        $card->update(['user_id' => null]);

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Tarjeta desvinculada.');
    }

    private function defaultNfcIdentifier(string $type): string
    {
        $prefix = $type === Card::TYPE_NFC_TAG_424_DNA ? 'nfc-424-dna' : 'qr-link';

        return $prefix.'-pending-'.Str::lower(Str::random(12));
    }
}
