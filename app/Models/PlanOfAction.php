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
        'description' => 'array',
    ];

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
