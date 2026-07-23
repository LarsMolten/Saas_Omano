<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'portfolio_item_id',
        'type',
        'url',
        'url_processed',
        'position',
        'processed',
    ];

    protected function casts(): array
    {
        return [
            'processed' => 'boolean',
        ];
    }

    public function portfolioItem(): BelongsTo
    {
        return $this->belongsTo(PortfolioItem::class, 'portfolio_item_id');
    }
}
