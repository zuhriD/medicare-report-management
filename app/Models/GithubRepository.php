<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GithubRepository extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner',
        'name',
        'full_name',
        'description',
        'default_branch',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function commits()
    {
        return $this->hasMany(GithubCommit::class, 'repository_id');
    }
}
