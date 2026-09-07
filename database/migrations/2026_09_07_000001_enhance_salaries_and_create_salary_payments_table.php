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
        Schema::table('salaries', function (Blueprint $table) {
            if (!Schema::hasColumn('salaries', 'previous_adjustment')) {
                $table->decimal('previous_adjustment', 10, 2)->default(0.00)->after('basic_salary');
            }
            if (!Schema::hasColumn('salaries', 'paid_amount')) {
                $table->decimal('paid_amount', 10, 2)->default(0.00)->after('net_salary');
            }
            if (!Schema::hasColumn('salaries', 'remaining_amount')) {
                $table->decimal('remaining_amount', 10, 2)->default(0.00)->after('paid_amount');
            }
        });

        if (!Schema::hasTable('salary_payments')) {
            Schema::create('salary_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('salary_id')->nullable()->constrained('salaries', 'salary_id')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->date('salary_month');
                $table->decimal('amount', 10, 2);
                $table->date('payment_date');
                $table->string('payment_method')->default('cash'); // cash, bank_transfer, cheque, other
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_payments');

        Schema::table('salaries', function (Blueprint $table) {
            if (Schema::hasColumn('salaries', 'previous_adjustment')) {
                $table->dropColumn('previous_adjustment');
            }
            if (Schema::hasColumn('salaries', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
            if (Schema::hasColumn('salaries', 'remaining_amount')) {
                $table->dropColumn('remaining_amount');
            }
        });
    }
};
