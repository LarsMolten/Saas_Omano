<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortfolioItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'title',
        'description',
        'event_date',
        'location',
        'budget_approx',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'budget_approx' => 'decimal:2',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(PortfolioMedia::class, 'portfolio_item_id');
    }
}
