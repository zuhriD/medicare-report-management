<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\Overtime;
use Carbon\Carbon;

class AttendanceWhatsAppNotificationService
{
    /**
     * Default static WhatsApp group link for testing / dummy flow.
     */
    public const DEFAULT_STATIC_GROUP_LINK = 'https://chat.whatsapp.com/I7yWf83d9H938ZkmEXAMPLE';

    /**
     * Get the active WhatsApp Group link (uses office setting or static dummy link).
     */
    public function getGroupLink(?string $officeGroupLink = null): string
    {
        return !empty($officeGroupLink) ? $officeGroupLink : self::DEFAULT_STATIC_GROUP_LINK;
    }

    /**
     * Resolve public photo URL for real selfie upload.
     */
    public function resolvePhotoUrl(?string $providedUrl = null, ?string $selfiePath = null): ?string
    {
        $url = !empty($providedUrl) ? $providedUrl : (!empty($selfiePath) ? app(SelfieStorageService::class)->getSelfieUrl($selfiePath) : null);

        if (empty($url)) {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        // Convert relative URL / path to full absolute URL
        return url($url);
    }

    /**
     * Format WhatsApp notification message for Regular Check-In.
     */
    public function formatCheckIn(Attendance $attendance, ?string $notes = null, ?float $accuracy = null, ?string $photoUrl = null): string
    {
        $user = $attendance->user;
        $office = $attendance->office;
        $tz = $office?->timezone ?? config('app.timezone');
        $timeStr = $attendance->check_in_at ? $attendance->check_in_at->setTimezone($tz)->format('H:i:s') : Carbon::now($tz)->format('H:i:s');
        $dateStr = $attendance->attendance_date ? Carbon::parse($attendance->attendance_date)->format('d M Y') : Carbon::now($tz)->format('d M Y');

        $gpsInfo = 'Lokasi Terverifikasi (Di Kantor)';
        if ($accuracy !== null) {
            $gpsInfo .= ' [Akurasi ±' . round($accuracy) . 'm]';
        }

        $photoLink = $this->resolvePhotoUrl($photoUrl, $attendance->check_in_selfie);

        $lines = [
            '━━━━━━━━━━━━━━━━━━━━━',
            '📍 *LAPORAN ABSENSI MASUK*',
            '━━━━━━━━━━━━━━━━━━━━━',
            '👤 *Nama:* ' . ($user?->name ?? 'Staff'),
            '🏢 *Kantor:* ' . ($office?->name ?? 'Kantor'),
            '📅 *Tanggal:* ' . $dateStr,
            '🕒 *Waktu Masuk:* ' . $timeStr . ' (' . $tz . ')',
            '📌 *Status GPS:* ' . $gpsInfo,
        ];

        if ($photoLink) {
            $lines[] = '📸 *Foto Selfie:* ' . $photoLink;
        }

        if ($notes) {
            $lines[] = '💬 *Catatan:* ' . $notes;
        }

        $lines[] = '━━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '_Medicare HR Attendance System_';

        return implode("\n", $lines);
    }

    /**
     * Format WhatsApp notification message for Break / Pause.
     */
    public function formatPause(Attendance $attendance, AttendanceBreak $break, ?string $photoUrl = null): string
    {
        $user = $attendance->user;
        $office = $attendance->office;
        $tz = $office?->timezone ?? config('app.timezone');
        $timeStr = $break->paused_at ? $break->paused_at->setTimezone($tz)->format('H:i:s') : Carbon::now($tz)->format('H:i:s');
        $photoLink = $this->resolvePhotoUrl($photoUrl, $break->paused_selfie);

        $lines = [
            '━━━━━━━━━━━━━━━━━━━━━',
            '⏸️ *IZIN KELUAR KANTOR (JEDA)*',
            '━━━━━━━━━━━━━━━━━━━━━',
            '👤 *Nama:* ' . ($user?->name ?? 'Staff'),
            '🏢 *Kantor:* ' . ($office?->name ?? 'Kantor'),
            '🕒 *Waktu Keluar:* ' . $timeStr . ' (' . $tz . ')',
            '📝 *Alasan Izin:* ' . ($break->reason ?: 'Istirahat / Keperluan'),
        ];

        if ($photoLink) {
            $lines[] = '📸 *Foto Selfie:* ' . $photoLink;
        }

        if ($break->notes) {
            $lines[] = '💬 *Catatan:* ' . $break->notes;
        }

        $lines[] = '━━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '_Status absensi dijeda otomatis hingga kembali ke kantor_';

        return implode("\n", $lines);
    }

    /**
     * Format WhatsApp notification message for Resume / Return to office.
     */
    public function formatResume(Attendance $attendance, AttendanceBreak $break, ?string $photoUrl = null): string
    {
        $user = $attendance->user;
        $office = $attendance->office;
        $tz = $office?->timezone ?? config('app.timezone');
        $timeStr = $break->resumed_at ? $break->resumed_at->setTimezone($tz)->format('H:i:s') : Carbon::now($tz)->format('H:i:s');
        $calcService = app(AttendanceCalculationService::class);
        $durationStr = $calcService->formatMinutesToDuration($break->duration_minutes ?? 0);
        $photoLink = $this->resolvePhotoUrl($photoUrl, $break->resumed_selfie);

        $lines = [
            '━━━━━━━━━━━━━━━━━━━━━',
            '▶️ *KEMBALI KE KANTOR (LANJUT KERJA)*',
            '━━━━━━━━━━━━━━━━━━━━━',
            '👤 *Nama:* ' . ($user?->name ?? 'Staff'),
            '🏢 *Kantor:* ' . ($office?->name ?? 'Kantor'),
            '🕒 *Waktu Kembali:* ' . $timeStr . ' (' . $tz . ')',
            '⏱️ *Durasi Izin/Jeda:* ' . $durationStr,
            '📌 *Status GPS:* Terverifikasi di Kantor',
        ];

        if ($photoLink) {
            $lines[] = '📸 *Foto Selfie:* ' . $photoLink;
        }

        $lines[] = '━━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '_Absensi dilanjutkan kembali_';

        return implode("\n", $lines);
    }

    /**
     * Format WhatsApp notification message for Regular Check-Out.
     */
    public function formatCheckOut(Attendance $attendance, ?string $notes = null, ?string $photoUrl = null): string
    {
        $user = $attendance->user;
        $office = $attendance->office;
        $tz = $office?->timezone ?? config('app.timezone');
        $checkInStr = $attendance->check_in_at ? $attendance->check_in_at->setTimezone($tz)->format('H:i:s') : '—';
        $checkOutStr = $attendance->check_out_at ? $attendance->check_out_at->setTimezone($tz)->format('H:i:s') : Carbon::now($tz)->format('H:i:s');
        
        $calcService = app(AttendanceCalculationService::class);
        $workingDuration = $calcService->formatMinutesToDuration($attendance->working_minutes ?? 0);
        $breakDuration = $calcService->formatMinutesToDuration($attendance->totalBreakMinutes());

        $allowanceStatus = $attendance->allowance_eligible ? '✅ Memenuhi Syarat' : '❌ Belum Memenuhi Syarat';
        $photoLink = $this->resolvePhotoUrl($photoUrl, $attendance->check_out_selfie);

        $lines = [
            '━━━━━━━━━━━━━━━━━━━━━',
            '🏁 *LAPORAN ABSENSI PULANG*',
            '━━━━━━━━━━━━━━━━━━━━━',
            '👤 *Nama:* ' . ($user?->name ?? 'Staff'),
            '🏢 *Kantor:* ' . ($office?->name ?? 'Kantor'),
            '⏳ *Jam Masuk:* ' . $checkInStr,
            '🕒 *Jam Pulang:* ' . $checkOutStr . ' (' . $tz . ')',
            '⏸️ *Total Jeda/Izin:* ' . $breakDuration,
            '⏱️ *Durasi Kerja Efektif:* ' . $workingDuration,
            '💰 *Tunjangan Hadir:* ' . $allowanceStatus,
        ];

        if ($photoLink) {
            $lines[] = '📸 *Foto Selfie:* ' . $photoLink;
        }

        if ($notes) {
            $lines[] = '💬 *Catatan:* ' . $notes;
        }

        $lines[] = '━━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '_Medicare HR Attendance System_';

        return implode("\n", $lines);
    }

    /**
     * Format WhatsApp notification message for Overtime Check-In.
     */
    public function formatOvertimeCheckIn(Overtime $overtime, ?string $notes = null, ?float $accuracy = null, ?string $photoUrl = null): string
    {
        $attendance = $overtime->attendance;
        $user = $attendance?->user;
        $office = $attendance?->office;
        $tz = $office?->timezone ?? config('app.timezone');
        $timeStr = $overtime->check_in_at ? $overtime->check_in_at->setTimezone($tz)->format('H:i:s') : Carbon::now($tz)->format('H:i:s');
        $photoLink = $this->resolvePhotoUrl($photoUrl, $overtime->check_in_selfie);

        $lines = [
            '━━━━━━━━━━━━━━━━━━━━━',
            '🔥 *NOTIFIKASI MULAI LEMBUR (OT)*',
            '━━━━━━━━━━━━━━━━━━━━━',
            '👤 *Nama:* ' . ($user?->name ?? 'Staff'),
            '🏢 *Kantor:* ' . ($office?->name ?? 'Kantor'),
            '🕒 *Waktu Mulai:* ' . $timeStr . ' (' . $tz . ')',
            '📌 *Status GPS:* Di Lokasi Lembur',
        ];

        if ($photoLink) {
            $lines[] = '📸 *Foto Selfie:* ' . $photoLink;
        }

        if ($notes) {
            $lines[] = '💬 *Catatan Tugas:* ' . $notes;
        }

        $lines[] = '━━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '_Medicare HR Attendance System_';

        return implode("\n", $lines);
    }

    /**
     * Format WhatsApp notification message for Overtime Check-Out.
     */
    public function formatOvertimeCheckOut(Overtime $overtime, ?string $notes = null, ?string $photoUrl = null): string
    {
        $attendance = $overtime->attendance;
        $user = $attendance?->user;
        $office = $attendance?->office;
        $tz = $office?->timezone ?? config('app.timezone');
        $inStr = $overtime->check_in_at ? $overtime->check_in_at->setTimezone($tz)->format('H:i:s') : '—';
        $outStr = $overtime->check_out_at ? $overtime->check_out_at->setTimezone($tz)->format('H:i:s') : Carbon::now($tz)->format('H:i:s');

        $calcService = app(AttendanceCalculationService::class);
        $otDuration = $calcService->formatMinutesToDuration($overtime->overtime_minutes ?? 0);
        $allowanceStatus = $overtime->allowance_eligible ? '✅ Memenuhi Syarat' : '❌ Belum Memenuhi';
        $photoLink = $this->resolvePhotoUrl($photoUrl, $overtime->check_out_selfie);

        $lines = [
            '━━━━━━━━━━━━━━━━━━━━━',
            '🔥 *LAPORAN SELESAI LEMBUR (OT)*',
            '━━━━━━━━━━━━━━━━━━━━━',
            '👤 *Nama:* ' . ($user?->name ?? 'Staff'),
            '🏢 *Kantor:* ' . ($office?->name ?? 'Kantor'),
            '🕒 *Mulai Lembur:* ' . $inStr,
            '🏁 *Selesai Lembur:* ' . $outStr . ' (' . $tz . ')',
            '⏱️ *Durasi Lembur:* ' . $otDuration,
            '💰 *Uang Lembur:* ' . $allowanceStatus,
        ];

        if ($photoLink) {
            $lines[] = '📸 *Foto Selfie:* ' . $photoLink;
        }

        if ($notes) {
            $lines[] = '💬 *Catatan Hasil:* ' . $notes;
        }

        $lines[] = '━━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '_Medicare HR Attendance System_';

        return implode("\n", $lines);
    }
}
