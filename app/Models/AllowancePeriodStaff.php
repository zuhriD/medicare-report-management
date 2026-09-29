<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AllowancePeriodStaff extends Model
{
    use HasFactory;

    protected $table = 'allowance_period_staff';

    protected $fillable = [
        'allowance_period_id',
        'user_id',
        'total_attendance_days',
        'total_attendance_amount',
        'total_overtime_minutes',
        'total_overtime_amount',
        'total_allowance',
        'payment_status',
        'paid_at',
        'paid_by',
        'payment_method',
        'payment_reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_attendance_days' => 'integer',
            'total_attendance_amount' => 'decimal:2',
            'total_overtime_minutes' => 'integer',
            'total_overtime_amount' => 'decimal:2',
            'total_allowance' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function allowancePeriod(): BelongsTo
    {
        return $this->belongsTo(AllowancePeriod::class, 'allowance_period_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AllowancePeriodItem::class, 'allowance_period_staff_id');
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isUnpaid(): bool
    {
        return $this->payment_status === 'unpaid';
    }

    public function getFormattedTotalAllowanceAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->total_allowance, 0, ',', '.');
    }

    public function getFormattedAttendanceAmountAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->total_attendance_amount, 0, ',', '.');
    }

    public function getFormattedOvertimeAmountAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->total_overtime_amount, 0, ',', '.');
    }
}
