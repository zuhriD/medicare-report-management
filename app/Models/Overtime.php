<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Overtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'overtime_date',
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
        'overtime_minutes',
        'allowance_eligible',
        'allowance_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'overtime_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'check_in_latitude' => 'float',
            'check_in_longitude' => 'float',
            'check_in_accuracy' => 'float',
            'check_out_latitude' => 'float',
            'check_out_longitude' => 'float',
            'check_out_accuracy' => 'float',
            'overtime_minutes' => 'integer',
            'allowance_eligible' => 'boolean',
            'allowance_amount' => 'decimal:2',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function isCheckedIn(): bool
    {
        return !is_null($this->check_in_at);
    }

    public function isCheckedOut(): bool
    {
        return !is_null($this->check_out_at);
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
        return $query->where('overtime_date', $date);
    }
}

