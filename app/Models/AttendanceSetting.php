<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'regular_check_in_start',
        'regular_check_out_end',
        'minimum_regular_minutes',
        'regular_allowance_amount',
        'overtime_check_in_start',
        'overtime_check_out_end',
        'minimum_overtime_minutes',
        'overtime_allowance_amount',
        'effective_from',
        'effective_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
            'minimum_regular_minutes' => 'integer',
            'regular_allowance_amount' => 'decimal:2',
            'minimum_overtime_minutes' => 'integer',
            'overtime_allowance_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEffectiveOn($query, $date)
    {
        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $date);
            });
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}

