<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_create_link_and_unlink_nfc_card(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);
        $client = User::factory()->create([
            'email' => 'cliente@vqr.test',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Panel admin')
            ->assertSee($client->email);

        $this->actingAs($admin)
            ->post(route('admin.cards.store'), [
                'type' => Card::TYPE_NFC_TAG_424_DNA,
                'label' => 'TAG camioneta',
                'nfc_identifier' => 'TAG-424-DNA-001',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $card = Card::where('nfc_identifier', 'TAG-424-DNA-001')->firstOrFail();

        $this->assertNull($card->user_id);
        $this->assertSame(Card::TYPE_NFC_TAG_424_DNA, $card->type);

        $this->actingAs($admin)
            ->post(route('admin.cards.attach', $card), [
                'user_id' => $client->id,
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame($client->id, $card->refresh()->user_id);

        $this->actingAs($admin)
            ->post(route('admin.cards.detach', $card))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertNull($card->refresh()->user_id);
    }

    public function test_admin_can_access_common_account_panel_without_subscription(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('account.show'))
            ->assertOk()
            ->assertSee('Panel admin')
            ->assertSee('Vista admin');
    }
}
