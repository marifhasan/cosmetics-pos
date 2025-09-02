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
            $table->string('variant_name'); // e.g., "50ml", "Large", "Red"
            $table->string('sku')->unique();
            $table->decimal('cost_price', 10, 2);
            $table->decimal('selling_price', 10, 2);
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock_level')->default(5); // For stock alerts
            $table->string('barcode')->unique()->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['product_id', 'is_active']);
            $table->index(['sku']);
            $table->index(['barcode']);
            $table->index(['stock_quantity', 'min_stock_level']); // For stock alerts
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
