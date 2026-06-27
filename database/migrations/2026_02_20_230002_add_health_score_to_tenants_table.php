<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedSmallInteger('health_score')->nullable()->after('onboarding_completed_at');
            $table->timestamp('health_score_calculated_at')->nullable()->after('health_score');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['health_score', 'health_score_calculated_at']);
        });
    }
};
