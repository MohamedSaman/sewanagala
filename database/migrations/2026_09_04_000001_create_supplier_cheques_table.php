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
        Schema::create('supplier_cheques', function (Blueprint $table) {
            $table->id();
            $table->string('cheque_number')->index();
            $table->date('cheque_date')->index();
            $table->string('bank_name');
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->string('payee_name')->nullable();
            $table->unsignedBigInteger('purchase_payment_id')->nullable()->index();
            $table->enum('status', ['pending', 'complete', 'return', 'cancelled'])->default('pending')->index();
            $table->string('cheque_photo_url')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('product_suppliers')->onDelete('cascade');
            $table->foreign('purchase_payment_id')->references('id')->on('purchase_payments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_cheques');
    }
};
