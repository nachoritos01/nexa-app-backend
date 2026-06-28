<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('agency_quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('number');
            $table->string('client_id')->nullable();
            $table->string('date')->nullable();
            $table->string('valid_until')->nullable();
            $table->string('status')->default('draft');
            $table->jsonb('items')->nullable();
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax', 8, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_quotes');
    }
};
