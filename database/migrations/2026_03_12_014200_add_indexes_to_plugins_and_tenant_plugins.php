<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('tenant_plugins', function (Blueprint $table) {
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::table('plugins', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('tenant_plugins', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'is_active']);
        });

        Schema::table('plugins', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'sort_order']);
        });
    }
};
