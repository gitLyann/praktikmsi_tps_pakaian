<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('period', 7);
            $table->decimal('sales_target', 14, 2)->default(0);
            $table->unsignedInteger('transaction_target')->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'period'], 'staff_targets_employee_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_targets');
    }
};
