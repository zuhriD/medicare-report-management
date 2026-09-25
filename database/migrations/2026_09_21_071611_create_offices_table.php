<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('address')->nullable();

            // Geofencing
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('attendance_radius_meter')->default(100);

            // Example: Asia/Kuala_Lumpur, Asia/Jakarta
            $table->string('timezone')->default('Asia/Kuala_Lumpur');

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offices');
    }
};
