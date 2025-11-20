<?php

declare(strict_types=1);

namespace Nexus\Budget\ValueObjects;

use Nexus\Budget\Enums\AlertSeverity;

/**
 * Utilization Alert value object
 * 
 * Immutable representation of a budget utilization alert.
 */
final readonly class UtilizationAlert
{
    public function __construct(
        private float $currentUtilization,
        private float $threshold,
        private AlertSeverity $severity,
        private string $message
    ) {}

    public function getCurrentUtilization(): float
    {
        return $this->currentUtilization;
    }

    public function getThreshold(): float
    {
        return $this->threshold;
    }

    public function getSeverity(): AlertSeverity
    {
        return $this->severity;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Check if threshold was exceeded
     */
    public function isThresholdExceeded(): bool
    {
        return $this->currentUtilization >= $this->threshold;
    }

    /**
     * Get formatted alert message
     */
    public function getFormattedMessage(): string
    {
        return sprintf(
            '[%s] Budget utilization at %.2f%% (threshold: %.2f%%) - %s',
            $this->severity->label(),
            $this->currentUtilization,
            $this->threshold,
            $this->message
        );
    }
}
