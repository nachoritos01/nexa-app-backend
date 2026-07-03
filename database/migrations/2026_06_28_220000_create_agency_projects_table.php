<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('agency_projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('client_id')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->default('pending');
            $table->string('priority')->default('medium');
            $table->decimal('budget', 14, 2)->default(0);
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->jsonb('team_members')->nullable();
            $table->jsonb('tasks')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->jsonb('comments')->nullable();
            $table->jsonb('files')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_projects');
    }
};
