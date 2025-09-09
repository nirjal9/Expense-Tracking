<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Expense extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id', 
        'category_id', 
        'amount', 
        'description', 
        'date',
        'is_auto_created',
        'source',
        'notification_type',
        'transaction_id',
        'merchant',
        'requires_approval',
        'auto_created_at',
        'approved_at',
        'rejected_at',
        'rejection_reason'
    ];
    
    protected $casts = [
        'date' => 'date',
        'is_auto_created' => 'boolean',
        'requires_approval' => 'boolean',
        'auto_created_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function autoCreatedExpense(): HasOne
    {
        return $this->hasOne(AutoCreatedExpense::class);
    }

    /**
     * Scope for auto-created expenses
     */
    public function scopeAutoCreated($query)
    {
        return $query->where('is_auto_created', true);
    }

    /**
     * Scope for manual expenses
     */
    public function scopeManual($query)
    {
        return $query->where('is_auto_created', false);
    }

    /**
     * Scope for expenses requiring approval
     */
    public function scopeRequiresApproval($query)
    {
        return $query->where('requires_approval', true);
    }

    /**
     * Scope for approved expenses
     */
    public function scopeApproved($query)
    {
        return $query->where('requires_approval', false)
                    ->whereNull('rejected_at');
    }

    /**
     * Scope for rejected expenses
     */
    public function scopeRejected($query)
    {
        return $query->whereNotNull('rejected_at');
    }

    /**
     * Check if expense is auto-created
     */
    public function isAutoCreated(): bool
    {
        return $this->is_auto_created;
    }

    /**
     * Check if expense requires approval
     */
    public function requiresApproval(): bool
    {
        return $this->requires_approval;
    }

    /**
     * Check if expense is approved
     */
    public function isApproved(): bool
    {
        return !$this->requires_approval && is_null($this->rejected_at);
    }

    /**
     * Check if expense is rejected
     */
    public function isRejected(): bool
    {
        return !is_null($this->rejected_at);
    }
}
