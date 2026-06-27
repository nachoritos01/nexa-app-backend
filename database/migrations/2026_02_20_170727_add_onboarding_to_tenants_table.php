<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->json('onboarding_steps')->nullable()->after('settings');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_steps');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['onboarding_steps', 'onboarding_completed_at']);
        });
    }
};
