<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'plan')) {
                $table->string('plan')->default('normal')->after('status')->index();
            }

            if (! Schema::hasColumn('subscriptions', 'vehicle_limit')) {
                $table->unsignedTinyInteger('vehicle_limit')->default(1)->after('plan');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'plan')) {
                $table->string('plan')->default('normal')->after('status')->index();
            }
        });

        DB::table('subscriptions')
            ->where('amount', '>=', 9990)
            ->update([
                'plan' => 'premium',
                'vehicle_limit' => 3,
            ]);

        DB::table('subscriptions')
            ->where('amount', '<', 9990)
            ->update([
                'plan' => 'normal',
                'vehicle_limit' => 1,
            ]);

        DB::table('payments')
            ->where('amount', '>=', 9990)
            ->update(['plan' => 'premium']);

        DB::table('payments')
            ->where('amount', '<', 9990)
            ->update(['plan' => 'normal']);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'plan')) {
                $table->dropColumn('plan');
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'vehicle_limit')) {
                $table->dropColumn('vehicle_limit');
            }

            if (Schema::hasColumn('subscriptions', 'plan')) {
                $table->dropColumn('plan');
            }
        });
    }
};
