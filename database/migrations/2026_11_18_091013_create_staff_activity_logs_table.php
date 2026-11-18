<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('activity_type', [
                'transaction_processed',
                'restock_requested',
                'restock_decided',
                'supplier_evaluated',
            ]);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->dateTime('occurred_at');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'occurred_at'], 'staff_activity_logs_employee_occurred_index');
            $table->unique(
                ['employee_id', 'activity_type', 'reference_type', 'reference_id'],
                'staff_activity_logs_idempotency_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_activity_logs');
    }
};
