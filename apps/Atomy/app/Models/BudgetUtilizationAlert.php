<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Budget\Enums\AlertSeverity;

/**
 * BudgetUtilizationAlert Eloquent Model
 * 
 * Multi-threshold alerting with acknowledgement workflow.
 * 
 * @property string $id
 * @property string $budget_id
 * @property string $period_id
 * @property float $utilization_percentage
 * @property float $allocated_amount
 * @property float $actual_amount
 * @property float $committed_amount
 * @property string $currency
 * @property AlertSeverity $severity
 * @property string $alert_type
 * @property string $message
 * @property bool $is_acknowledged
 * @property \DateTimeInterface|null $acknowledged_at
 * @property string|null $acknowledged_by
 * @property string|null $acknowledgement_notes
 * @property bool $notification_sent
 * @property \DateTimeInterface|null $notification_sent_at
 * @property array|null $notification_channels
 * @property \DateTimeInterface $triggered_at
 */
class BudgetUtilizationAlert extends Model
{
    use HasUlids;

    protected $fillable = [
        'budget_id',
        'period_id',
        'utilization_percentage',
        'allocated_amount',
        'actual_amount',
        'committed_amount',
        'currency',
        'severity',
        'alert_type',
        'message',
        'is_acknowledged',
        'acknowledged_at',
        'acknowledged_by',
        'acknowledgement_notes',
        'notification_sent',
        'notification_sent_at',
        'notification_channels',
        'triggered_at',
    ];

    protected $casts = [
        'utilization_percentage' => 'decimal:2',
        'allocated_amount' => 'decimal:4',
        'actual_amount' => 'decimal:4',
        'committed_amount' => 'decimal:4',
        'severity' => AlertSeverity::class,
        'is_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
        'notification_sent' => 'boolean',
        'notification_sent_at' => 'datetime',
        'notification_channels' => 'array',
        'triggered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Eloquent Relationships

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'budget_id');
    }

    // Scopes

    public function scopeUnacknowledged($query)
    {
        return $query->where('is_acknowledged', false);
    }

    public function scopeBySeverity($query, AlertSeverity $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', AlertSeverity::Critical);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('triggered_at', '>=', now()->subHours($hours));
    }

    // Methods

    public function acknowledge(string $acknowledgedBy, ?string $notes = null): void
    {
        $this->update([
            'is_acknowledged' => true,
            'acknowledged_at' => now(),
            'acknowledged_by' => $acknowledgedBy,
            'acknowledgement_notes' => $notes,
        ]);
    }

    // Computed Accessors

    public function getIsOverdueAttribute(): bool
    {
        if ($this->is_acknowledged) {
            return false;
        }

        // Critical alerts should be acknowledged within 4 hours
        if ($this->severity === AlertSeverity::Critical) {
            return $this->triggered_at->diffInHours(now()) > 4;
        }

        // High severity within 24 hours
        if ($this->severity === AlertSeverity::High) {
            return $this->triggered_at->diffInHours(now()) > 24;
        }

        return false;
    }

    public function getTimeToAcknowledgeAttribute(): ?int
    {
        if (!$this->is_acknowledged) {
            return null;
        }

        return $this->triggered_at->diffInMinutes($this->acknowledged_at);
    }
}
