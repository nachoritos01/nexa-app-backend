<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('agency_expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->text('description');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('category')->nullable();
            $table->string('date')->nullable();
            $table->string('status')->default('pending');
            $table->string('supplier_id')->nullable();
            $table->string('project_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_expenses');
    }
};
