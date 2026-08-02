<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportImage extends Model
{
    use HasFactory;

    protected $fillable = ['daily_report_id', 'image_path', 'caption'];

    public function dailyReport()
    {
        return $this->belongsTo(DailyReport::class);
    }
}
