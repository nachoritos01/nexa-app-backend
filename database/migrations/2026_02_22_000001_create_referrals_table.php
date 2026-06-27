<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('referred_tenant_id')->unique()->constrained('tenants')->onDelete('cascade');
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();

            $table->index('referrer_tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
