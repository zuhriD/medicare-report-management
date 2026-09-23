<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'office_id',
        'attendance_setting_id',
        'attendance_date',
        'check_in_at',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_accuracy',
        'check_in_selfie',
        'check_out_at',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_accuracy',
        'check_out_selfie',
        'working_minutes',
        'allowance_eligible',
        'allowance_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'check_in_latitude' => 'float',
            'check_in_longitude' => 'float',
            'check_in_accuracy' => 'float',
            'check_out_latitude' => 'float',
            'check_out_longitude' => 'float',
            'check_out_accuracy' => 'float',
            'working_minutes' => 'integer',
            'allowance_eligible' => 'boolean',
            'allowance_amount' => 'decimal:2',
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

    public function attendanceSetting(): BelongsTo
    {
        return $this->belongsTo(AttendanceSetting::class);
    }

    public function overtime(): HasOne
    {
        return $this->hasOne(Overtime::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function activeBreak(): ?AttendanceBreak
    {
        return $this->breaks()->whereNull('resumed_at')->latest('paused_at')->first();
    }

    public function isPaused(): bool
    {
        return $this->isCheckedIn() && !$this->isCheckedOut() && $this->breaks()->whereNull('resumed_at')->exists();
    }

    public function totalBreakMinutes(): int
    {
        return (int) $this->breaks()->whereNotNull('duration_minutes')->sum('duration_minutes');
    }

    public function isCheckedIn(): bool
    {
        return !is_null($this->check_in_at);
    }

    public function isCheckedOut(): bool
    {
        return !is_null($this->check_out_at);
    }

    public function isOvertimeEligible(): bool
    {
        if (!$this->isCheckedOut()) {
            return false;
        }

        $minMinutes = $this->attendanceSetting?->minimum_regular_minutes ?? 360;
        return $this->working_minutes >= $minMinutes;
    }

    public function getCheckInSelfieUrlAttribute(): string
    {
        return app(\App\Services\SelfieStorageService::class)->getSelfieUrl($this->check_in_selfie);
    }

    public function getCheckOutSelfieUrlAttribute(): string
    {
        return app(\App\Services\SelfieStorageService::class)->getSelfieUrl($this->check_out_selfie);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('attendance_date', $date);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}

