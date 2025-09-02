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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->unique(); // Primary identifier
            $table->string('name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->text('address')->nullable();
            $table->date('birthdate')->nullable();
            $table->integer('loyalty_points')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['phone']);
            $table->index(['email']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
