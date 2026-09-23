<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'holiday_date',
        'name',
        'office_id',
        'country_code',
        'is_national_holiday',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'is_national_holiday' => 'boolean',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function scopeForOfficeAndDate($query, ?int $officeId, string $date)
    {
        return $query->whereDate('holiday_date', $date)
            ->where(function ($q) use ($officeId) {
                $q->whereNull('office_id');
                if ($officeId) {
                    $q->orWhere('office_id', $officeId);
                }
            });
    }

    public function scopeInPeriod($query, string $startDate, string $endDate, ?int $officeId = null)
    {
        return $query->whereBetween('holiday_date', [$startDate, $endDate])
            ->where(function ($q) use ($officeId) {
                $q->whereNull('office_id');
                if ($officeId) {
                    $q->orWhere('office_id', $officeId);
                }
            });
    }
}
