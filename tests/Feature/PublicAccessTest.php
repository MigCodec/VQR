<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\DocumentType;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_subscription_can_view_card_vehicle_list_and_documents(): void
    {
        [$card, $vehicle] = $this->createPublicVehicle(activeSubscription: true);

        $this->get(route('public.cards.show', $card->short_code))
            ->assertOk()
            ->assertSee($vehicle->plate);

        $this->get(route('public.vehicles.show', $vehicle->public_token))
            ->assertOk()
            ->assertSee('Revisión técnica')
            ->assertSee('SOAP')
            ->assertSee('Permiso de circulación');
    }

    public function test_expired_subscription_cannot_view_public_documents(): void
    {
        [$card, $vehicle] = $this->createPublicVehicle(activeSubscription: false);

        $this->get(route('public.cards.show', $card->short_code))
            ->assertStatus(402)
            ->assertSee('Los documentos no están disponibles')
            ->assertDontSee($vehicle->plate);

        $this->get(route('public.vehicles.show', $vehicle->public_token))
            ->assertStatus(402)
            ->assertSee('Los documentos no están disponibles')
            ->assertDontSee('Revisión técnica');
    }

    public function test_card_only_shows_vehicles_with_active_user_relationship(): void
    {
        [$oldCard, $vehicle] = $this->createPublicVehicle(activeSubscription: true);
        $oldUser = $oldCard->user;

        $oldUser->vehicles()->updateExistingPivot($vehicle->id, [
            'ends_at' => now()->subDay(),
        ]);

        $newUser = User::factory()->create();
        Subscription::create([
            'user_id' => $newUser->id,
            'status' => 'active',
            'amount' => 4990,
            'currency' => 'CLP',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
        ]);
        $newCard = Card::create([
            'user_id' => $newUser->id,
            'nfc_identifier' => 'NFC-new-owner',
            'short_code' => 'new-owner',
            'status' => 'active',
        ]);
        $newUser->vehicles()->attach($vehicle, [
            'role' => 'owner',
            'starts_at' => now(),
            'is_primary' => true,
        ]);
        $this->get(route('public.cards.show', $oldCard->short_code))
            ->assertOk()
            ->assertDontSee($vehicle->plate);

        $this->get(route('public.cards.show', $newCard->short_code))
            ->assertOk()
            ->assertSee($vehicle->plate);
    }

    private function createPublicVehicle(bool $activeSubscription): array
    {
        $user = User::factory()->create();

        Subscription::create([
            'user_id' => $user->id,
            'status' => 'active',
            'amount' => 4990,
            'currency' => 'CLP',
            'starts_at' => now()->subYear(),
            'expires_at' => $activeSubscription ? now()->addYear() : now()->subDay(),
        ]);

        $card = Card::create([
            'user_id' => $user->id,
            'nfc_identifier' => 'NFC-'.$user->id,
            'short_code' => 'card-'.$user->id,
            'status' => 'active',
        ]);

        $vehicle = Vehicle::create([
            'public_token' => 'vehicle-'.$user->id,
            'plate' => 'ABCD-12',
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2022,
            'status' => 'active',
        ]);

        $user->vehicles()->attach($vehicle, [
            'role' => 'owner',
            'starts_at' => now(),
            'is_primary' => true,
        ]);

        foreach ([
            'revision-tecnica' => 'Revisión técnica',
            'soap' => 'SOAP',
            'permiso-circulacion' => 'Permiso de circulación',
        ] as $slug => $name) {
            $type = DocumentType::create([
                'name' => $name,
                'slug' => $slug,
                'is_required' => true,
            ]);

            VehicleDocument::create([
                'vehicle_id' => $vehicle->id,
                'document_type_id' => $type->id,
                'expires_at' => now()->addMonth()->toDateString(),
                'status' => 'valid',
            ]);
        }

        return [$card, $vehicle];
    }
}
