<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_settings', 'late_tolerance_minute')) {
                $table->unsignedInteger('late_tolerance_minute')
                    ->default(0)
                    ->after('regular_check_in_start');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_settings', 'late_tolerance_minute')) {
                $table->dropColumn('late_tolerance_minute');
            }
        });
    }
};
