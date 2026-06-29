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
        $disk = Storage::disk('local');

        $vehicle = Vehicle::query()
            ->where('public_token', $publicToken)
            ->where('status', 'active')
            ->with('activeUsers.activeSubscription')
            ->firstOrFail();

        abort_unless($document->vehicle_id === $vehicle->id, 404);

        if (! $this->hasActiveSubscribedUser($vehicle)) {
            return response()->view('public.subscription-expired', [
                'vehicle' => $vehicle,
            ], 402);
        }

        abort_unless($document->file_path && $disk->exists($document->file_path), 404);

        $fileName = basename($document->file_path);
        $mimeType = $disk->mimeType($document->file_path) ?: 'application/octet-stream';

        return response()->file($disk->path($document->file_path), [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function hasActiveSubscribedUser(Vehicle $vehicle): bool
    {
        return $vehicle->activeUsers->contains(fn ($user) => $user->hasActiveSubscription());
    }
}
