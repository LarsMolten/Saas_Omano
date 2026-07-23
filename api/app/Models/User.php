<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'email_verified_at',
        'email_verification_token',
        'phone_verified',
        'phone_verification_code',
        'bio',
        'category',
        'city',
        'latitude',
        'longitude',
        'average_rating',
        'rating_count',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_token',
        'phone_verification_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'average_rating' => 'decimal:2',
            'rating_count' => 'integer',
        ];
    }

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->role,
        ];
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function isPrestataire(): bool
    {
        return $this->role === 'prestataire';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'provider_id');
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class, 'provider_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class, 'user_id');
    }

    public function favoritedBy(): HasMany
    {
        return $this->hasMany(Favorite::class, 'provider_id');
    }

    public function quoteRequests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class, 'user_id');
    }

    public function receivedQuotes(): HasMany
    {
        return $this->hasMany(QuoteRequest::class, 'provider_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    public function receivedReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'provider_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
