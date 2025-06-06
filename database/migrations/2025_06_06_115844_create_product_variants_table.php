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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('sku')->unique();
            $table->decimal('price', 10, 2);
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->integer('inventory_quantity')->default(0);
            $table->boolean('track_inventory')->default(true);
            $table->string('inventory_policy')->default('deny'); // deny, continue
            $table->string('fulfillment_service')->default('manual');
            $table->string('option1')->nullable(); // Size, Color, etc.
            $table->string('option2')->nullable();
            $table->string('option3')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('weight_unit')->default('kg');
            $table->string('barcode')->nullable();
            $table->json('image')->nullable();
            $table->boolean('requires_shipping')->default(true);
            $table->boolean('taxable')->default(true);
            $table->integer('position')->default(1);
            $table->timestamps();
            
            $table->index(['product_id', 'position']);
            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
