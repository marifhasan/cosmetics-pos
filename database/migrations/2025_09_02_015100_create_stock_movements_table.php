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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->enum('movement_type', ['sale', 'purchase', 'adjustment', 'return']);
            $table->unsignedBigInteger('reference_id')->nullable(); // Links to sale_id, purchase_id, etc.
            $table->integer('quantity_change'); // Positive for in, negative for out
            $table->integer('previous_quantity');
            $table->integer('new_quantity');
            $table->text('notes')->nullable();
            $table->timestamp('movement_date');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['product_variant_id', 'movement_date']);
            $table->index(['movement_type', 'movement_date']);
            $table->index(['reference_id', 'movement_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
