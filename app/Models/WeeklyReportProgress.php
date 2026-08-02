<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyReportProgress extends Model
{
    use HasFactory;

    protected $table = 'weekly_report_progress';

    protected $fillable = ['weekly_report_id', 'sub_module_id', 'progress_percentage'];

    public function weeklyReport()
    {
        return $this->belongsTo(WeeklyReport::class);
    }

    public function subModule()
    {
        return $this->belongsTo(SubModule::class);
    }
}
