<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'reason',
        'paused_at',
        'paused_latitude',
        'paused_longitude',
        'paused_accuracy',
        'paused_selfie',
        'resumed_at',
        'resumed_latitude',
        'resumed_longitude',
        'resumed_accuracy',
        'resumed_selfie',
        'duration_minutes',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'paused_at' => 'datetime',
            'resumed_at' => 'datetime',
            'paused_latitude' => 'float',
            'paused_longitude' => 'float',
            'paused_accuracy' => 'float',
            'resumed_latitude' => 'float',
            'resumed_longitude' => 'float',
            'resumed_accuracy' => 'float',
            'duration_minutes' => 'integer',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOpen(): bool
    {
        return is_null($this->resumed_at);
    }

    public function isCompleted(): bool
    {
        return !is_null($this->resumed_at);
    }
}
