<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllowancePeriodItem extends Model
{
    use HasFactory;

    protected $table = 'allowance_period_items';

    protected $fillable = [
        'allowance_period_staff_id',
        'attendance_id',
        'overtime_id',
        'item_type',
        'item_date',
        'amount',
        'duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'item_date' => 'date',
            'amount' => 'decimal:2',
            'duration_minutes' => 'integer',
        ];
    }

    public function allowancePeriodStaff(): BelongsTo
    {
        return $this->belongsTo(AllowancePeriodStaff::class, 'allowance_period_staff_id');
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }

    public function overtime(): BelongsTo
    {
        return $this->belongsTo(Overtime::class, 'overtime_id');
    }
}
