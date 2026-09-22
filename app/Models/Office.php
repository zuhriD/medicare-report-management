<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Office extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'attendance_radius_meter',
        'timezone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'attendance_radius_meter' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function attendanceSettings(): HasMany
    {
        return $this->hasMany(AttendanceSetting::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    /**
     * Get the active attendance setting/policy for a given date.
     */
    public function getActiveSettingForDate($date = null): ?AttendanceSetting
    {
        $date = $date ?? now($this->timezone ?? config('app.timezone'))->toDateString();

        return $this->attendanceSettings()
            ->where('is_active', true)
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $date);
            })
            ->latest('effective_from')
            ->first();
    }
}

