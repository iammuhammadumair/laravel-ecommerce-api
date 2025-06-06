<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->unique();
            $table->decimal('price', 10, 2);
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->integer('inventory_quantity')->default(0);
            $table->boolean('track_inventory')->default(true);
            $table->string('status')->default('active'); // active, inactive, archived
            $table->string('vendor')->nullable();
            $table->string('product_type')->nullable();
            $table->json('tags')->nullable();
            $table->json('images')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('weight_unit')->default('kg');
            $table->boolean('requires_shipping')->default(true);
            $table->json('seo')->nullable(); // title, description, keywords
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
