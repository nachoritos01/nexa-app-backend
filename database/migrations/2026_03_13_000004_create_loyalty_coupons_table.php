<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('loyalty_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loyalty_reward_id')->constrained()->cascadeOnDelete();
            $table->string('code', 15)->unique();
            $table->string('type', 30);
            $table->decimal('value', 8, 2)->nullable();
            $table->date('expires_at');
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['customer_id', 'used_at']);
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_coupons');
    }
};
