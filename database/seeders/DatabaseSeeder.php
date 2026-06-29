<?php

namespace Database\Seeders;

use App\Models\Card;
use App\Models\DocumentType;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::query()->firstOrCreate([
            'email' => 'usuario@vqr.test',
        ], [
            'name' => 'Usuario VQR',
            'password' => bcrypt('password'),
        ]);

        Subscription::query()->updateOrCreate([
            'user_id' => $user->id,
        ], [
            'status' => 'active',
            'plan' => 'premium',
            'vehicle_limit' => 3,
            'amount' => 10000,
            'currency' => 'CLP',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
        ]);

        $card = Card::query()->updateOrCreate([
            'short_code' => 'demo',
        ], [
            'user_id' => $user->id,
            'type' => Card::TYPE_QR_LINK,
            'nfc_identifier' => 'VQR-DEMO-NFC-001',
            'label' => 'Tarjeta demo',
            'status' => 'active',
        ]);

        $vehicle = Vehicle::query()->updateOrCreate([
            'public_token' => 'vehiculo-demo',
        ], [
            'plate' => 'ABCD-12',
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2022,
            'status' => 'active',
        ]);

        $user->vehicles()->syncWithoutDetaching([
            $vehicle->id => [
                'role' => 'owner',
                'starts_at' => $vehicle->created_at ?? now(),
                'ends_at' => null,
                'is_primary' => true,
            ],
        ]);

        $types = collect([
            ['name' => 'Revisión técnica', 'slug' => 'revision-tecnica', 'sort_order' => 10],
            ['name' => 'SOAP', 'slug' => 'soap', 'sort_order' => 20],
            ['name' => 'Permiso de circulación', 'slug' => 'permiso-circulacion', 'sort_order' => 30],
        ])->mapWithKeys(function (array $attributes) {
            $type = DocumentType::query()->updateOrCreate([
                'slug' => $attributes['slug'],
            ], $attributes + ['is_required' => true]);

            return [$type->slug => $type];
        });

        VehicleDocument::query()->updateOrCreate([
            'vehicle_id' => $vehicle->id,
            'document_type_id' => $types['revision-tecnica']->id,
        ], [
            'folio' => 'RT-2026-001',
            'issued_at' => now()->subMonths(2)->toDateString(),
            'expires_at' => now()->addMonths(10)->toDateString(),
            'status' => 'valid',
            'source_url' => null,
        ]);

        VehicleDocument::query()->updateOrCreate([
            'vehicle_id' => $vehicle->id,
            'document_type_id' => $types['soap']->id,
        ], [
            'folio' => 'SOAP-2026-001',
            'issued_at' => now()->subMonth()->toDateString(),
            'expires_at' => now()->addMonths(8)->toDateString(),
            'status' => 'valid',
            'source_url' => null,
        ]);

        VehicleDocument::query()->updateOrCreate([
            'vehicle_id' => $vehicle->id,
            'document_type_id' => $types['permiso-circulacion']->id,
        ], [
            'folio' => 'PC-2026-001',
            'issued_at' => now()->subMonths(3)->toDateString(),
            'expires_at' => now()->addDays(20)->toDateString(),
            'status' => 'valid',
            'source_url' => null,
        ]);
    }
}
