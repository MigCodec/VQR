<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Support\Facades\Storage;

class PublicVehicleController extends Controller
{
    public function show(string $publicToken)
    {
        $vehicle = Vehicle::query()
            ->where('public_token', $publicToken)
            ->where('status', 'active')
            ->with(['activeUsers.activeSubscription', 'documents.type'])
            ->firstOrFail();

        if (! $this->hasActiveSubscribedUser($vehicle)) {
            return response()->view('public.subscription-expired', [
                'vehicle' => $vehicle,
            ], 402);
        }

        return view('public.vehicle', [
            'vehicle' => $vehicle,
            'documents' => $vehicle->documents->sortBy('type.sort_order'),
        ]);
    }

    public function document(string $publicToken, VehicleDocument $document)
    {
        $vehicle = $this->authorizedVehicleForDocument($publicToken, $document);

        abort_unless($this->resolveDocumentPath($document), 404);

        return view('public.document-viewer', [
            'vehicle' => $vehicle,
            'document' => $document->loadMissing('type'),
            'fileUrl' => route('public.vehicles.documents.file', [$vehicle->public_token, $document]),
        ]);
    }

    public function documentFile(string $publicToken, VehicleDocument $document)
    {
        $this->authorizedVehicleForDocument($publicToken, $document);

        $absolutePath = $this->resolveDocumentPath($document);

        abort_unless($absolutePath, 404);

        $fileName = basename($absolutePath);
        $mimeType = $this->documentMimeType($absolutePath);

        return response()->file($absolutePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizedVehicleForDocument(string $publicToken, VehicleDocument $document): Vehicle
    {
        $vehicle = Vehicle::query()
            ->where('public_token', $publicToken)
            ->where('status', 'active')
            ->with('activeUsers.activeSubscription')
            ->firstOrFail();

        abort_unless($document->vehicle_id === $vehicle->id, 404);

        if (! $this->hasActiveSubscribedUser($vehicle)) {
            abort(response()->view('public.subscription-expired', [
                'vehicle' => $vehicle,
            ], 402));
        }

        return $vehicle;
    }

    private function hasActiveSubscribedUser(Vehicle $vehicle): bool
    {
        return $vehicle->activeUsers->contains(fn ($user) => $user->hasActiveSubscription());
    }

    private function resolveDocumentPath(VehicleDocument $document): ?string
    {
        if (! $document->file_path) {
            return null;
        }

        $relativePath = ltrim(str_replace('\\', '/', $document->file_path), '/');
        $disk = Storage::disk('local');

        if ($disk->exists($relativePath)) {
            $realPath = realpath($disk->path($relativePath));

            if ($realPath && is_file($realPath)) {
                return $realPath;
            }
        }

        foreach ([
            storage_path('app/private/'.$relativePath),
            storage_path('app/'.$relativePath),
            storage_path('app/public/'.$relativePath),
        ] as $candidatePath) {
            $realPath = realpath($candidatePath);

            if ($realPath && is_file($realPath) && str_starts_with($realPath, storage_path('app'))) {
                return $realPath;
            }
        }

        return null;
    }

    private function documentMimeType(string $absolutePath): string
    {
        $detectedMimeType = mime_content_type($absolutePath) ?: null;

        if ($detectedMimeType && $detectedMimeType !== 'application/x-empty') {
            return $detectedMimeType;
        }

        return match (strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
