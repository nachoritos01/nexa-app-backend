<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('agency_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('number');
            $table->string('client_id')->nullable();
            $table->string('project_id')->nullable();
            $table->string('quote_id')->nullable();
            $table->string('date')->nullable();
            $table->string('due_date')->nullable();
            $table->string('status')->default('draft');
            $table->jsonb('items')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax', 8, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_invoices');
    }
};
