<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Services\DocumentAiExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AccountVehicleDocumentController extends Controller
{
    public function store(Request $request, Vehicle $vehicle, DocumentType $documentType, DocumentAiExtractor $extractor)
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
            'expires_at' => ['nullable', 'date'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $disk = Storage::disk('local');
        $path = $request->file('document')->store("vehicle-documents/{$vehicle->id}", 'local');
        $absolutePath = $disk->path($path);
        $mimeType = $request->file('document')->getMimeType() ?: 'application/octet-stream';
        $extracted = $extractor->extract($absolutePath, $mimeType, $documentType);
        $issuedAt = $data['issued_at'] ?? $extracted['issued_at'] ?? null;
        $expiresAt = $data['expires_at'] ?? $extracted['expires_at'] ?? null;
        $folio = $data['folio'] ?? $extracted['folio'] ?? null;

        $existing = VehicleDocument::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('document_type_id', $documentType->id)
            ->first();

        if ($existing?->file_path && $disk->exists($existing->file_path)) {
            $disk->delete($existing->file_path);
        }

        VehicleDocument::updateOrCreate([
            'vehicle_id' => $vehicle->id,
            'document_type_id' => $documentType->id,
        ], [
            'folio' => $folio,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
            'status' => $expiresAt ? 'valid' : 'pending',
            'file_path' => $path,
            'source_url' => null,
            'notes' => $expiresAt ? null : 'No se pudo detectar automaticamente la fecha de vencimiento.',
            'ai_extracted' => $extracted !== [],
            'ai_extracted_at' => $extracted !== [] ? now() : null,
            'ai_metadata' => $extracted ?: null,
        ]);

        return redirect()
            ->route('account.show')
            ->with('status', $expiresAt
                ? 'Documento actualizado.'
                : 'Documento subido. Revisa la fecha de vencimiento porque no se pudo detectar automaticamente.');
    }
}
