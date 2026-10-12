<?php

use App\Enums\AttendanceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'status')) {
                $table->enum('status', [AttendanceStatus::ON_TIME->value, AttendanceStatus::LATE->value])
                    ->default(AttendanceStatus::ON_TIME->value)
                    ->after('check_in_selfie');
            }

            if (!Schema::hasColumn('attendances', 'late_minutes')) {
                $table->unsignedInteger('late_minutes')
                    ->default(0)
                    ->after('status');
            }
        });

        // Backfill existing records
        $attendances = DB::table('attendances')
            ->join('attendance_settings', 'attendances.attendance_setting_id', '=', 'attendance_settings.id')
            ->join('offices', 'attendances.office_id', '=', 'offices.id')
            ->whereNotNull('attendances.check_in_at')
            ->select(
                'attendances.id',
                'attendances.check_in_at',
                'attendance_settings.regular_check_in_start',
                'offices.timezone'
            )
            ->get();

        foreach ($attendances as $att) {
            $tz = $att->timezone ?? config('app.timezone', 'Asia/Kuala_Lumpur');
            $checkIn = \Carbon\Carbon::parse($att->check_in_at)->setTimezone($tz);
            $scheduledStart = \Carbon\Carbon::parse($checkIn->format('Y-m-d') . ' ' . $att->regular_check_in_start, $tz);

            $diffSeconds = $checkIn->getTimestamp() - $scheduledStart->getTimestamp();

            if ($diffSeconds > 0) {
                $lateMinutes = (int) ceil($diffSeconds / 60);
                DB::table('attendances')
                    ->where('id', $att->id)
                    ->update([
                        'status' => AttendanceStatus::LATE->value,
                        'late_minutes' => $lateMinutes,
                    ]);
            } else {
                DB::table('attendances')
                    ->where('id', $att->id)
                    ->update([
                        'status' => AttendanceStatus::ON_TIME->value,
                        'late_minutes' => 0,
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('attendances', 'status')) {
                $columnsToDrop[] = 'status';
            }
            if (Schema::hasColumn('attendances', 'late_minutes')) {
                $columnsToDrop[] = 'late_minutes';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
