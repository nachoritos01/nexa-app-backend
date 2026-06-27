<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->default('heroicon-o-puzzle-piece');
            $table->string('category')->default('general');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_free')->default(true);
            $table->integer('price_monthly')->default(0);
            $table->string('stripe_price_id')->nullable();
            $table->json('included_in_plans')->default('[]');
            $table->json('required_modules')->default('[]');
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
