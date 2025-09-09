<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantCategoryMapping extends Model
{
    protected $fillable = [
        'merchant',
        'category_id',
        'confidence',
        'usage_count',
        'last_used'
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
        'last_used' => 'datetime',
    ];

    /**
     * Get the category that owns the merchant mapping.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
