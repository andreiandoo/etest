<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar_url',
        'locale',
        'timezone',
        'preferences',
        'email_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Accesul în panoul de administrare.
     *
     * Aceeași condiție ca gate-ul `access-admin`: nu vrem două surse de adevăr
     * pentru cine e administrator.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'preferences' => 'array',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /** @return HasMany<TestAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }

    /** @return HasOne<UserStat, $this> */
    public function stats(): HasOne
    {
        return $this->hasOne(UserStat::class);
    }

    /** @return HasMany<UserAchievement, $this> */
    public function achievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    /** @return HasMany<UserXpEvent, $this> */
    public function xpEvents(): HasMany
    {
        return $this->hasMany(UserXpEvent::class);
    }

    /** @return BelongsToMany<TestDefinition, $this> */
    public function favoriteTests(): BelongsToMany
    {
        return $this->belongsToMany(TestDefinition::class, 'favorite_tests', 'user_id', 'test_id')
            ->withTimestamps();
    }
}
