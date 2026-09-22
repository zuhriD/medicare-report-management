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
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->index();
            $table->string('name');
            $table->foreignId('office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->string('country_code', 2)->nullable()->default('ID'); // ID, MY, etc.
            $table->boolean('is_national_holiday')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['holiday_date', 'office_id', 'country_code'], 'unique_holiday_per_office_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
