<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubModule extends Model
{
    use HasFactory;

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
