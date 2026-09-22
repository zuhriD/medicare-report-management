<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_id')
                ->unique()
                ->constrained('attendances')
                ->cascadeOnDelete();

            $table->date('overtime_date');

            /*
            |--------------------------------------------------------------------------
            | OT CHECK IN
            |--------------------------------------------------------------------------
            */

            $table->timestamp('check_in_at')->nullable();

            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->decimal('check_in_accuracy', 8, 2)->nullable();

            $table->string('check_in_selfie')->nullable();

            /*
            |--------------------------------------------------------------------------
            | OT CHECK OUT
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

            $table->unsignedInteger('overtime_minutes')
                ->default(0);

            $table->boolean('allowance_eligible')
                ->default(false);

            // Snapshot allowance
            $table->decimal('allowance_amount', 12, 2)
                ->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('overtime_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtimes');
    }
};
