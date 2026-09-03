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
        Schema::create('manual_supplier_returns', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number');
            $table->date('return_date');
            $table->foreignId('supplier_id')->nullable()->constrained('product_suppliers')->nullOnDelete();
            $table->string('supplier_name')->nullable();
            $table->foreignId('product_id')->constrained('product_details')->onDelete('restrict');
            $table->decimal('return_quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('return_condition', 30)->default('usable');
            $table->string('return_reason', 50)->default('damaged');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_supplier_returns');
    }
};
