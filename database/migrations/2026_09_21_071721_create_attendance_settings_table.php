<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('office_id')
                ->constrained('offices')
                ->cascadeOnDelete();

            // Regular Attendance
            $table->time('regular_check_in_start')
                ->default('08:00:00');

            $table->time('regular_check_out_end')
                ->default('17:00:00');

            $table->unsignedInteger('minimum_regular_minutes')
                ->default(360);

            $table->decimal('regular_allowance_amount', 12, 2)
                ->default(0);

            // Overtime
            $table->time('overtime_check_in_start')
                ->default('18:00:00');

            $table->time('overtime_check_out_end')
                ->default('22:00:00');

            $table->unsignedInteger('minimum_overtime_minutes')
                ->default(120);

            $table->decimal('overtime_allowance_amount', 12, 2)
                ->default(0);

            // Policy Period
            $table->date('effective_from');
            $table->date('effective_until')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Custom short index name
            $table->index(
                ['office_id', 'effective_from', 'effective_until'],
                'attendance_policy_period_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
