<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->string('plan')->default('normal')->index();
            $table->unsignedTinyInteger('vehicle_limit')->default(1);
            $table->unsignedInteger('amount')->default(4990);
            $table->string('currency', 3)->default('CLP');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->foreignId('last_payment_id')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->default('mercado_pago');
            $table->string('provider_payment_id')->nullable()->index();
            $table->string('provider_preference_id')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->string('plan')->default('normal')->index();
            $table->unsignedInteger('amount')->default(4990);
            $table->string('currency', 3)->default('CLP');
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('qr_link')->index();
            $table->string('nfc_identifier')->unique();
            $table->string('short_code')->unique();
            $table->string('label')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('public_token')->unique();
            $table->string('plate')->index();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('vin')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

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

        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('vehicle_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();
            $table->string('public_token')->unique();
            $table->string('folio')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->string('file_path')->nullable();
            $table->string('source_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_id', 'document_type_id']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreign('last_payment_id')->references('id')->on('payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['last_payment_id']);
        });

        Schema::dropIfExists('vehicle_documents');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('card_vehicle');
        Schema::dropIfExists('user_vehicle');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('cards');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
    }
};
