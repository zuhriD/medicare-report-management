<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubModule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['module_id', 'name'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function dailyReports()
    {
        return $this->hasMany(DailyReport::class);
    }
}
