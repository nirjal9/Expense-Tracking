<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoCreatedExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_id',
        'user_id',
        'source',
        'notification_type',
        'raw_data',
        'confidence_score',
        'status',
        'approved_at',
        'rejected_at',
        'rejection_reason'
    ];

    protected $casts = [
        'raw_data' => 'array',
        'confidence_score' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Get the expense that owns the auto-created expense record
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Get the user that owns the auto-created expense record
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for pending approval
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    /**
     * Scope for approved
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for high confidence
     */
    public function scopeHighConfidence($query, float $threshold = 0.8)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    /**
     * Scope for specific source
     */
    public function scopeFromSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope for specific notification type
     */
    public function scopeFromNotificationType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Get confidence level as string
     */
    public function getConfidenceLevelAttribute(): string
    {
        if ($this->confidence_score >= 0.9) {
            return 'Very High';
        } elseif ($this->confidence_score >= 0.8) {
            return 'High';
        } elseif ($this->confidence_score >= 0.6) {
            return 'Medium';
        } elseif ($this->confidence_score >= 0.4) {
            return 'Low';
        } else {
            return 'Very Low';
        }
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending_approval' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            default => 'gray'
        };
    }

    /**
     * Check if expense is pending approval
     */
    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    /**
     * Check if expense is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if expense is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Get formatted raw data
     */
    public function getFormattedRawDataAttribute(): string
    {
        if (!$this->raw_data) {
            return 'No data available';
        }

        $formatted = [];
        foreach ($this->raw_data as $key => $value) {
            $formatted[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
        }

        return implode(', ', $formatted);
    }
}