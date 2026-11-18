<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_performance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('period', 7);
            $table->unsignedInteger('transactions_count')->default(0);
            $table->decimal('total_sales', 14, 2)->default(0);
            $table->decimal('avg_order_value', 14, 2)->default(0);
            $table->unsignedInteger('restock_submitted')->default(0);
            $table->unsignedInteger('restock_approved')->default(0);
            $table->unsignedInteger('evaluations_count')->default(0);
            $table->decimal('avg_response_hours', 8, 2)->default(0);
            $table->decimal('target_achievement', 6, 2)->default(0);
            $table->decimal('performance_score', 8, 4)->nullable();
            $table->unsignedInteger('rank')->nullable();
            $table->enum('status', ['green', 'yellow', 'red'])->default('green');
            $table->timestamps();

            $table->unique(['employee_id', 'period'], 'staff_performance_snapshots_employee_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_performance_snapshots');
    }
};
