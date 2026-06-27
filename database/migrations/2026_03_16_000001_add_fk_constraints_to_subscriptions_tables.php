<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Clean orphaned records before adding constraints
        DB::statement('DELETE FROM subscription_items WHERE subscription_id NOT IN (SELECT id FROM subscriptions)');
        DB::statement('DELETE FROM subscriptions WHERE tenant_id NOT IN (SELECT id FROM tenants)');

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::table('subscription_items', function (Blueprint $table) {
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });

        Schema::table('subscription_items', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
        });
    }
};
