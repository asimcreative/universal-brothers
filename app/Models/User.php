<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
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
            'is_active' => 'boolean',
            'onboarding_dismissed_at' => 'datetime',
            'tour_step' => 'integer',
        ];
    }

    /** Guide sections this admin has marked as read. */
    public function guideCompletions(): HasMany
    {
        return $this->hasMany(AdminGuideCompletion::class);
    }

    public function trainingProgress(): HasMany
    {
        return $this->hasMany(AdminTrainingProgress::class);
    }

    /**
     * The welcome panel shows until the admin dismisses it or finishes the
     * tour; after that only an explicit restart brings the tour back.
     */
    public function shouldSeeOnboarding(): bool
    {
        return $this->onboarding_dismissed_at === null && $this->tour_status !== 'completed';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }
}
