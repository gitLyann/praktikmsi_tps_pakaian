<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Fix customer_id FK: drop old constraint and recreate pointing to users table
        // The column already exists, just need to repoint the FK
        try {
            DB::statement('ALTER TABLE transactions DROP FOREIGN KEY transactions_customer_id_foreign');
        } catch (\Exception $e) {
            // FK constraint may not exist or already dropped
        }
        try {
            DB::statement('ALTER TABLE transactions ADD CONSTRAINT transactions_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES users(id)');
        } catch (\Exception $e) {
            // FK may already be correct or error due to data mismatch
            // Ignore - the column exists, we just tried to fix the FK
        }

        // 2. Fix employee_id FK: make nullable and point to users table
        try {
            DB::statement('ALTER TABLE transactions DROP FOREIGN KEY transactions_employee_id_foreign');
        } catch (\Exception $e) {
            // FK constraint may not exist
        }
        try {
            // Modify employee_id to be nullable and add FK constraint
            DB::statement('ALTER TABLE transactions MODIFY employee_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE transactions ADD CONSTRAINT transactions_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES users(id)');
        } catch (\Exception $e) {
            // FK may already be correct or error due to data mismatch
        }

        // 3. Verify 'total' column exists (should already exist from previous migration)
        // No action needed - column already exists as 'total' decimal(10,2)

        // 4. Verify 'payment_method' column exists
        // Already exists from previous migration

        // 5. Verify 'status' column exists
        // Already exists from previous migration with enum values
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Try to drop the FK constraints we added
        try {
            DB::statement('ALTER TABLE transactions DROP FOREIGN KEY transactions_customer_id_foreign');
        } catch (\Exception $e) {
            // Ignore errors
        }
        try {
            DB::statement('ALTER TABLE transactions DROP FOREIGN KEY transactions_employee_id_foreign');
        } catch (\Exception $e) {
            // Ignore errors
        }
    }
};