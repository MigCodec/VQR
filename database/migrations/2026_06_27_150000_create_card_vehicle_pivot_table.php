<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('card_vehicle')) {
            Schema::create('card_vehicle', function (Blueprint $table) {
                $table->id();
                $table->foreignId('card_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable()->index();
                $table->timestamps();

                $table->index(['card_id', 'ends_at']);
                $table->unique(['card_id', 'vehicle_id', 'starts_at']);
            });
        }

        DB::table('cards')
            ->orderBy('id')
            ->get(['id', 'user_id', 'created_at', 'updated_at'])
            ->each(function (object $card): void {
                $vehicleIds = DB::table('user_vehicle')
                    ->where('user_id', $card->user_id)
                    ->whereNull('ends_at')
                    ->pluck('vehicle_id');

                foreach ($vehicleIds as $vehicleId) {
                    DB::table('card_vehicle')->updateOrInsert([
                        'card_id' => $card->id,
                        'vehicle_id' => $vehicleId,
                        'starts_at' => $card->created_at,
                    ], [
                        'ends_at' => null,
                        'created_at' => $card->created_at ?? now(),
                        'updated_at' => $card->updated_at ?? now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_vehicle');
    }
};
