<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WeeklyReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'week_number',
        'start_date',
        'end_date',
        'executive_summary',
        'plan_of_action'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function weeklyReportProgresses()
    {
        return $this->hasMany(WeeklyReportProgress::class);
    }
}
