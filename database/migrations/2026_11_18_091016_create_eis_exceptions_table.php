<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eis_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('period', 7);
            $table->string('type', 80);
            $table->enum('severity', ['danger', 'warning', 'info']);
            $table->text('message');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'period', 'type'], 'eis_exceptions_employee_period_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eis_exceptions');
    }
};
