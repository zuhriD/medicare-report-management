<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('office_id')
                ->constrained('offices')
                ->restrictOnDelete();

            $table->foreignId('attendance_setting_id')
                ->constrained('attendance_settings')
                ->restrictOnDelete();

            $table->date('attendance_date');

            /*
            |--------------------------------------------------------------------------
            | CHECK IN
            |--------------------------------------------------------------------------
            */

            $table->timestamp('check_in_at')->nullable();

            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->decimal('check_in_accuracy', 8, 2)->nullable();

            $table->string('check_in_selfie')->nullable();

            /*
            |--------------------------------------------------------------------------
            | CHECK OUT
            |--------------------------------------------------------------------------
            */

            $table->timestamp('check_out_at')->nullable();

            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->decimal('check_out_accuracy', 8, 2)->nullable();

            $table->string('check_out_selfie')->nullable();

            /*
            |--------------------------------------------------------------------------
            | CALCULATION
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('working_minutes')
                ->default(0);

            $table->boolean('allowance_eligible')
                ->default(false);

            // Snapshot allowance
            $table->decimal('allowance_amount', 12, 2)
                ->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | CONSTRAINTS
            |--------------------------------------------------------------------------
            */

            // One regular attendance per user per day
            $table->unique(
                ['user_id', 'attendance_date'],
                'attendances_user_date_unique'
            );

            $table->index([
                'office_id',
                'attendance_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
