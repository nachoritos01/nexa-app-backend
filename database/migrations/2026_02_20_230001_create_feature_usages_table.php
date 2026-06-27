<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('feature_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('feature', 50);
            $table->timestamp('used_at');

            $table->index(['tenant_id', 'feature', 'used_at']);
            $table->index(['tenant_id', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_usages');
    }
};
