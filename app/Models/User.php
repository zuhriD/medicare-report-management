<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @method bool hasRole(string|array $roles, ?string $guard = null)
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'office_id',
        'name',
        'email',
        'username',
        'github_username',
        'github_email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class);
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(DailyReport::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function offices(): BelongsToMany
    {
        return $this->belongsToMany(Office::class)->withTimestamps();
    }

    /**
     * Get all assigned offices for this user (including primary office and assigned offices).
     *
     * @return \Illuminate\Support\Collection<int, Office>
     */
    public function getAllAssignedOffices()
    {
        $assigned = $this->offices()->where('is_active', true)->get();

        if ($this->office && $this->office->is_active && !$assigned->contains('id', $this->office_id)) {
            $assigned->prepend($this->office);
        }

        if ($assigned->isEmpty() && $this->office) {
            $assigned->push($this->office);
        }

        return $assigned;
    }


    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function githubCommits(): HasMany
    {
        return $this->hasMany(GithubCommit::class);
    }

    public function allowancePeriodStaff(): HasMany
    {
        return $this->hasMany(AllowancePeriodStaff::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true; // Let spatie permissions handle specific resource access
    }
}
