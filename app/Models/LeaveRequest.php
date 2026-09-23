<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'office_id',
        'leave_type',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'attachment_path',
        'status',
        'normal_fine_amount',
        'adjusted_fine_amount',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_days' => 'integer',
            'normal_fine_amount' => 'decimal:2',
            'adjusted_fine_amount' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeCoversDate($query, $date)
    {
        return $query->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date);
    }

    public function getFormattedPeriodText(bool $withDates = true): string
    {
        $start = \Carbon\Carbon::parse($this->start_date)->locale('id');
        $end = \Carbon\Carbon::parse($this->end_date)->locale('id');

        $startDay = $start->isoFormat('dddd');
        $endDay = $end->isoFormat('dddd');
        $daysSuffix = $this->total_days ? " ({$this->total_days} hari kerja)" : '';

        if ($this->start_date->eq($this->end_date)) {
            $dateStr = $withDates ? ", {$start->isoFormat('D MMMM Y')}" : '';
            return "hari {$startDay}{$dateStr}{$daysSuffix}";
        }

        $startDateStr = $withDates ? ", {$start->isoFormat('D MMMM Y')}" : '';
        $endDateStr = $withDates ? ", {$end->isoFormat('D MMMM Y')}" : '';

        return "mulai hari {$startDay}{$startDateStr} hingga hari {$endDay}{$endDateStr}{$daysSuffix}";
    }

    public function getLeaveTypeLabel(): string
    {
        return match ($this->leave_type) {
            'sick' => 'Sakit (Sick Leave)',
            'permission' => 'Izin Keperluan',
            'annual_leave' => 'Cuti Tahunan',
            default => 'Izin',
        };
    }

    public function getFormattedChatTemplate(string $recipient = 'Dr. Adnan'): string
    {
        $userName = $this->user?->name ?? 'Staff';
        $officeName = $this->office?->name ?? ($this->user?->office?->name ?? 'Medicare');
        $leaveType = $this->getLeaveTypeLabel();
        $reason = trim($this->reason ?? 'keperluan izin');
        $periodText = $this->getFormattedPeriodText(true);

        $planText = "{$reason} {$periodText}";

        return <<<TEXT
*Kepada Yth.*
{$recipient}

Dengan hormat,

Saya yang bertanda tangan di bawah ini:

*Nama: {$userName}*
*Kantor: {$officeName}*
*Jenis Izin: {$leaveType}*

Dengan ini ingin menyampaikan permohonan izin sekaligus rencana untuk *{$planText}*.

Sehubungan dengan hal tersebut, saya memohon izin kepada {$recipient} untuk dapat melaksanakan rencana tersebut. Saya akan memastikan pekerjaan dan tanggung jawab yang perlu diselesaikan telah diatur dengan baik agar tidak mengganggu pekerjaan selama saya berada di luar.

Demikian permohonan ini saya sampaikan. Besar harapan saya agar {$recipient} dapat memberikan izin. Atas perhatian dan pengertiannya, saya mengucapkan terima kasih.

Hormat saya,

*{$userName}*
TEXT;
    }

    public function getWhatsAppChatTemplate(string $recipient = 'Dr. Adnan'): string
    {
        $userName = $this->user?->name ?? 'Staff';
        $officeName = $this->office?->name ?? ($this->user?->office?->name ?? 'Medicare');
        $leaveType = $this->getLeaveTypeLabel();
        $reason = trim($this->reason ?? 'keperluan izin');
        $periodText = $this->getFormattedPeriodText(true);

        $planText = "{$reason} {$periodText}";

        return <<<TEXT
*Kepada Yth.*
{$recipient}

Dengan hormat,

Saya yang bertanda tangan di bawah ini:

*Nama: {$userName}*
*Kantor: {$officeName}*
*Jenis Izin: {$leaveType}*

Dengan ini ingin menyampaikan permohonan izin sekaligus rencana untuk *{$planText}*.

Sehubungan dengan hal tersebut, saya memohon izin kepada {$recipient} untuk dapat melaksanakan rencana tersebut. Saya akan memastikan pekerjaan dan tanggung jawab yang perlu diselesaikan telah diatur dengan baik agar tidak mengganggu pekerjaan selama saya berada di luar.

Demikian permohonan ini saya sampaikan. Besar harapan saya agar {$recipient} dapat memberikan izin. Atas perhatian dan pengertiannya, saya mengucapkan terima kasih.

Hormat saya,

*{$userName}*
TEXT;
    }
}
