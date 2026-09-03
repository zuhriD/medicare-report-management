<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'sub_module_id', 'report_date', 'description'];

    protected $casts = [
        'report_date' => 'date',
        'description' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subModule()
    {
        return $this->belongsTo(SubModule::class);
    }

    public function reportImages()
    {
        return $this->hasMany(ReportImage::class);
    }
}
