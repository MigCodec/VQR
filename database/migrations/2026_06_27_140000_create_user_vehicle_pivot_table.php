<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_vehicle')) {
            Schema::create('user_vehicle', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
                $table->string('role')->default('owner');
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable()->index();
                $table->boolean('is_primary')->default(true);
                $table->timestamps();

                $table->index(['user_id', 'ends_at']);
                $table->unique(['user_id', 'vehicle_id', 'role', 'starts_at']);
            });
        }

        if (Schema::hasColumn('vehicles', 'user_id')) {
            DB::table('vehicles')
                ->whereNotNull('user_id')
                ->orderBy('id')
                ->get(['id', 'user_id', 'created_at', 'updated_at'])
                ->each(function (object $vehicle): void {
                    DB::table('user_vehicle')->updateOrInsert([
                        'user_id' => $vehicle->user_id,
                        'vehicle_id' => $vehicle->id,
                        'role' => 'owner',
                        'starts_at' => $vehicle->created_at,
                    ], [
                        'ends_at' => null,
                        'is_primary' => true,
                        'created_at' => $vehicle->created_at ?? now(),
                        'updated_at' => $vehicle->updated_at ?? now(),
                    ]);
                });

            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('vehicles', 'user_id')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });

            DB::table('user_vehicle')
                ->whereNull('ends_at')
                ->where('is_primary', true)
                ->orderBy('id')
                ->get(['user_id', 'vehicle_id'])
                ->each(function (object $relation): void {
                    DB::table('vehicles')
                        ->where('id', $relation->vehicle_id)
                        ->update(['user_id' => $relation->user_id]);
                });
        }

        Schema::dropIfExists('user_vehicle');
    }
};
