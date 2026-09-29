<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class AllowancePeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'office_id',
        'start_date',
        'end_date',
        'status',
        'total_amount',
        'total_paid_amount',
        'total_unpaid_amount',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_amount' => 'decimal:2',
            'total_paid_amount' => 'decimal:2',
            'total_unpaid_amount' => 'decimal:2',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function periodStaff(): HasMany
    {
        return $this->hasMany(AllowancePeriodStaff::class, 'allowance_period_id');
    }

    public function items(): HasManyThrough
    {
        return $this->hasManyThrough(AllowancePeriodItem::class, AllowancePeriodStaff::class);
    }

    public function getStaffCountAttribute(): int
    {
        return $this->periodStaff()->count();
    }

    public function getPaidStaffCountAttribute(): int
    {
        return $this->periodStaff()->where('payment_status', 'paid')->count();
    }

    public function getUnpaidStaffCountAttribute(): int
    {
        return $this->periodStaff()->where('payment_status', 'unpaid')->count();
    }
}
