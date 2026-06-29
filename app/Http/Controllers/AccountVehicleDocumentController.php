<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AccountVehicleDocumentController extends Controller
{
    public function store(Request $request, Vehicle $vehicle, DocumentType $documentType)
    {
        DocumentType::ensureRequiredTypes();

        if (! Auth::check()) {
            return redirect()->route('auth.google.redirect');
        }

        $user = Auth::user();

        if (! $user->hasActiveSubscription()) {
            return redirect()->route('billing.show');
        }

        abort_unless($user->activeVehicles()->whereKey($vehicle->id)->exists(), 403);

        $data = $request->validate([
            'folio' => ['nullable', 'string', 'max:120'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['required', 'date'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $path = $request->file('document')->store("vehicle-documents/{$vehicle->id}");

        $existing = VehicleDocument::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('document_type_id', $documentType->id)
            ->first();

        if ($existing?->file_path && Storage::exists($existing->file_path)) {
            Storage::delete($existing->file_path);
        }

        VehicleDocument::updateOrCreate([
            'vehicle_id' => $vehicle->id,
            'document_type_id' => $documentType->id,
        ], [
            'folio' => $data['folio'] ?? null,
            'issued_at' => $data['issued_at'] ?? null,
            'expires_at' => $data['expires_at'],
            'status' => 'valid',
            'file_path' => $path,
            'source_url' => null,
            'notes' => null,
        ]);

        return redirect()
            ->route('account.show')
            ->with('status', 'Documento actualizado.');
    }
}
