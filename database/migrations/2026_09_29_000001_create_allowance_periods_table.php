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
        Schema::create('allowance_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('active'); // draft, active, completed
            $table->decimal('total_amount', 14, 2)->default(0.00);
            $table->decimal('total_paid_amount', 14, 2)->default(0.00);
            $table->decimal('total_unpaid_amount', 14, 2)->default(0.00);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allowance_periods');
    }
};
