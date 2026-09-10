<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GithubCommit extends Model
{
    use HasFactory;

    protected $fillable = [
        'repository_id',
        'sha',
        'short_sha',
        'user_id',
        'message',
        'author_name',
        'author_email',
        'author_username',
        'authored_at',
        'committed_at',
        'url',
        'additions',
        'deletions',
        'files_changed',
    ];

    protected $casts = [
        'authored_at' => 'datetime',
        'committed_at' => 'datetime',
        'additions' => 'integer',
        'deletions' => 'integer',
        'files_changed' => 'integer',
    ];

    public function repository()
    {
        return $this->belongsTo(GithubRepository::class, 'repository_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
