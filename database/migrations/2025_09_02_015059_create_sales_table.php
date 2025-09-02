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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number')->unique(); // Auto-generated
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Cashier
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->integer('points_earned')->default(0);
            $table->enum('payment_method', ['cash', 'card', 'digital']);
            $table->enum('payment_status', ['pending', 'completed', 'refunded'])->default('completed');
            $table->text('notes')->nullable();
            $table->timestamp('sale_date');
            $table->timestamps();
            
            $table->index(['customer_id', 'sale_date']);
            $table->index(['user_id', 'sale_date']);
            $table->index(['payment_status']);
            $table->index(['sale_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
