<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicle_documents', 'ai_extracted')) {
                $table->boolean('ai_extracted')->default(false)->after('notes');
            }

            if (! Schema::hasColumn('vehicle_documents', 'ai_extracted_at')) {
                $table->timestamp('ai_extracted_at')->nullable()->after('ai_extracted');
            }

            if (! Schema::hasColumn('vehicle_documents', 'ai_metadata')) {
                $table->json('ai_metadata')->nullable()->after('ai_extracted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_documents', function (Blueprint $table) {
            if (Schema::hasColumn('vehicle_documents', 'ai_metadata')) {
                $table->dropColumn('ai_metadata');
            }

            if (Schema::hasColumn('vehicle_documents', 'ai_extracted_at')) {
                $table->dropColumn('ai_extracted_at');
            }

            if (Schema::hasColumn('vehicle_documents', 'ai_extracted')) {
                $table->dropColumn('ai_extracted');
            }
        });
    }
};
