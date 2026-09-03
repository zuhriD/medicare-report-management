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
            get: function ($value) {
                if (empty($value)) {
                    return [];
                }
                if (is_array($value)) {
                    return $value;
                }
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return array_values($decoded);
                }
                $clean = trim(strip_tags($value));
                if (empty($clean)) {
                    return [trim($value)];
                }
                $lines = array_values(array_filter(array_map('trim', preg_split('/[\n\r]+|(?<=\s)-\s/', $clean))));
                return !empty($lines) ? $lines : [$clean];
            },
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
