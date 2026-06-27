<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('cancellation_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('reason', 50);
            $table->text('details')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('reason');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_surveys');
    }
};
