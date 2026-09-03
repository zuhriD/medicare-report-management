<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanOfAction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'description',
        'start_date',
        'module_id',
        'sub_module_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected function description(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => \App\Models\DailyReport::parseTasks($value),
            set: function ($value) {
                if (is_array($value)) {
                    return json_encode(array_values(array_filter($value, fn($item) => filled($item))));
                }
                return $value;
            }
        );
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function subModule()
    {
        return $this->belongsTo(SubModule::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
