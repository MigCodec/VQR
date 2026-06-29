<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\DocumentType;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_panel_creates_account_card_and_shows_upload_forms(): void
    {
        [$user, $vehicle, $type] = $this->createLicensedVehicle();

        $this->actingAs($user)
            ->get(route('account.show'))
            ->assertOk()
            ->assertSee($vehicle->plate)
            ->assertSee('Revision tecnica')
            ->assertSee('Tarjeta de la cuenta')
            ->assertSee(route('public.cards.qr', $user->cards()->first()->short_code), false)
            ->assertSee('Subir documento');

        $card = Card::firstOrFail();

        $this->assertSame($user->id, $card->user_id);
        $this->assertSame('Tarjeta cuenta VQR', $card->label);
        $this->assertDatabaseHas('cards', [
            'user_id' => $user->id,
            'label' => 'Tarjeta cuenta VQR',
        ]);
        $this->assertDatabaseCount('card_vehicle', 0);

        $this->actingAs($user)
            ->get(route('account.cards.qr', $card))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8')
            ->assertSee('<svg', false);
    }

    public function test_user_can_upload_required_vehicle_document(): void
    {
        Storage::fake('local');

        [$user, $vehicle, $type] = $this->createLicensedVehicle();

        $this->actingAs($user)
            ->post(route('account.vehicles.documents.store', [$vehicle, $type]), [
                'folio' => 'RT-001',
                'issued_at' => now()->subMonth()->toDateString(),
                'expires_at' => now()->addYear()->toDateString(),
                'document' => UploadedFile::fake()->create('revision.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect(route('account.show'));

        $document = VehicleDocument::firstOrFail();

        $this->assertSame($vehicle->id, $document->vehicle_id);
        $this->assertSame($type->id, $document->document_type_id);
        $this->assertSame('valid', $document->status);
        $this->assertNotNull($document->public_token);
        Storage::disk('local')->assertExists($document->file_path);

        $documentUrl = route('public.vehicles.documents.show', [$vehicle->public_token, $document->public_token]);

        $this->assertStringContainsString($document->public_token, $documentUrl);
        $this->assertStringNotContainsString('/documents/'.$document->id, $documentUrl);

        $this->get($documentUrl)
            ->assertOk()
            ->assertSee('Visualizador de documento')
            ->assertSee(route('public.vehicles.documents.file', [$vehicle->public_token, $document->public_token]), false);

        $this->get(route('public.vehicles.documents.file', [$vehicle->public_token, $document->public_token]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="'.basename($document->file_path).'"');
    }

    public function test_public_document_view_supports_legacy_storage_app_path(): void
    {
        [$user, $vehicle, $type] = $this->createLicensedVehicle();

        $relativePath = "vehicle-documents-legacy-test/{$vehicle->id}/revision.pdf";
        $absolutePath = storage_path("app/{$relativePath}");

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, "%PDF-1.4\n% legacy test\n");

        $document = VehicleDocument::create([
            'vehicle_id' => $vehicle->id,
            'document_type_id' => $type->id,
            'expires_at' => now()->addYear()->toDateString(),
            'status' => 'valid',
            'file_path' => $relativePath,
        ]);

        $this->get(route('public.vehicles.documents.file', [$vehicle->public_token, $document->public_token]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="revision.pdf"');

        File::deleteDirectory(storage_path('app/vehicle-documents-legacy-test'));
    }

    public function test_public_document_view_supports_private_prefixed_path(): void
    {
        [$user, $vehicle, $type] = $this->createLicensedVehicle();

        $relativePath = "vehicle-documents-private-prefix-test/{$vehicle->id}/revision.pdf";
        $absolutePath = storage_path("app/private/{$relativePath}");

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, "%PDF-1.4\n% private prefix test\n");

        $document = VehicleDocument::create([
            'vehicle_id' => $vehicle->id,
            'document_type_id' => $type->id,
            'expires_at' => now()->addYear()->toDateString(),
            'status' => 'valid',
            'file_path' => 'private/'.$relativePath,
        ]);

        $this->get(route('public.vehicles.documents.show', [$vehicle->public_token, $document->public_token]))
            ->assertOk()
            ->assertSee('Visualizador de documento');

        $this->get(route('public.vehicles.documents.file', [$vehicle->public_token, $document->public_token]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="revision.pdf"');

        File::deleteDirectory(storage_path('app/private/vehicle-documents-private-prefix-test'));
    }

    public function test_account_panel_shows_default_document_uploads_even_without_seeded_document_types(): void
    {
        [$user, $vehicle] = $this->createLicensedVehicle(withDocumentType: false);

        $this->actingAs($user)
            ->get(route('account.show'))
            ->assertOk()
            ->assertSee($vehicle->plate)
            ->assertSee('Revision tecnica')
            ->assertSee('SOAP')
            ->assertSee('Permiso de circulacion')
            ->assertSee('Subir documento');
    }

    public function test_normal_license_can_add_only_one_vehicle(): void
    {
        $user = User::factory()->create();
        Subscription::create([
            'user_id' => $user->id,
            'status' => 'active',
            'plan' => 'normal',
            'vehicle_limit' => 1,
            'amount' => 4990,
            'currency' => 'CLP',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
        ]);

        $this->actingAs($user)
            ->post(route('account.vehicles.store'), [
                'plate' => 'ONE-001',
                'brand' => 'Toyota',
                'model' => 'Yaris',
            ])
            ->assertRedirect(route('account.show'));

        $this->actingAs($user)
            ->post(route('account.vehicles.store'), [
                'plate' => 'TWO-002',
                'brand' => 'Honda',
                'model' => 'Civic',
            ])
            ->assertRedirect(route('account.show'));

        $this->assertSame(1, $user->activeVehicles()->count());
        $this->assertDatabaseCount('card_vehicle', 0);
    }

    public function test_premium_license_can_add_up_to_three_vehicles(): void
    {
        $user = User::factory()->create();
        Subscription::create([
            'user_id' => $user->id,
            'status' => 'active',
            'plan' => 'premium',
            'vehicle_limit' => 3,
            'amount' => 9990,
            'currency' => 'CLP',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
        ]);

        foreach (['AAA-001', 'BBB-002', 'CCC-003'] as $plate) {
            $this->actingAs($user)
                ->post(route('account.vehicles.store'), [
                    'plate' => $plate,
                ])
                ->assertRedirect(route('account.show'));
        }

        $this->actingAs($user)
            ->post(route('account.vehicles.store'), [
                'plate' => 'DDD-004',
            ])
            ->assertRedirect(route('account.show'));

        $this->assertSame(3, $user->activeVehicles()->count());
        $this->assertDatabaseCount('card_vehicle', 0);
    }

    private function createLicensedVehicle(bool $withDocumentType = true): array
    {
        $user = User::factory()->create();
        Subscription::create([
            'user_id' => $user->id,
            'status' => 'active',
            'plan' => 'normal',
            'vehicle_limit' => 1,
            'amount' => 4990,
            'currency' => 'CLP',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
        ]);

        $vehicle = Vehicle::create([
            'public_token' => 'vehicle-'.$user->id,
            'plate' => 'TEST-10',
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'status' => 'active',
        ]);
        $user->vehicles()->attach($vehicle, [
            'role' => 'owner',
            'starts_at' => now(),
            'is_primary' => true,
        ]);

        $type = null;

        if ($withDocumentType) {
            $type = DocumentType::create([
                'name' => 'Revisión técnica',
                'slug' => 'revision-tecnica',
                'is_required' => true,
                'sort_order' => 10,
            ]);
        }

        return [$user, $vehicle, $type];
    }
}
