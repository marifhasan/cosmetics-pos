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
        Schema::create('loyalty_point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('transaction_type', ['earned', 'redeemed', 'adjusted']);
            $table->integer('points_change');
            $table->integer('previous_points');
            $table->integer('new_points');
            $table->string('description');
            $table->timestamp('transaction_date');
            $table->timestamps();
            
            $table->index(['customer_id', 'transaction_date']);
            $table->index(['transaction_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_point_transactions');
    }
};
