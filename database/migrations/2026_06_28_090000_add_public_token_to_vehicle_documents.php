<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('vehicle_documents', 'public_token')) {
            DB::table('vehicle_documents')
                ->whereNull('public_token')
                ->orderBy('id')
                ->each(function (object $document): void {
                    DB::table('vehicle_documents')
                        ->where('id', $document->id)
                        ->update(['public_token' => (string) Str::uuid()]);
                });

            return;
        }

        Schema::table('vehicle_documents', function (Blueprint $table) {
            $table->string('public_token')->nullable()->after('document_type_id');
        });

        DB::table('vehicle_documents')
            ->whereNull('public_token')
            ->orderBy('id')
            ->each(function (object $document): void {
                DB::table('vehicle_documents')
                    ->where('id', $document->id)
                    ->update(['public_token' => (string) Str::uuid()]);
            });

        Schema::table('vehicle_documents', function (Blueprint $table) {
            $table->unique('public_token');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('vehicle_documents', 'public_token')) {
            return;
        }

        Schema::table('vehicle_documents', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }
};
