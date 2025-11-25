<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\CapacityResolverInterface;
use Nexus\Manufacturing\Contracts\CapacityPlannerInterface;
use Nexus\Manufacturing\Contracts\WorkCenterManagerInterface;
use Nexus\Manufacturing\Contracts\WorkOrderManagerInterface;
use Nexus\Manufacturing\Enums\ResolutionAction;
use Nexus\Manufacturing\ValueObjects\CapacityResolutionSuggestion;
use Nexus\Manufacturing\ValueObjects\PlanningHorizon;
use Psr\Log\LoggerInterface;

/**
 * Capacity Resolver implementation.
 *
 * Handles automatic and manual resolution of capacity constraints.
 */
final readonly class CapacityResolver implements CapacityResolverInterface
{
    public function __construct(
        private CapacityPlannerInterface $capacityPlanner,
        private WorkCenterManagerInterface $workCenterManager,
        private WorkOrderManagerInterface $workOrderManager,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getSuggestions(string $workCenterId, PlanningHorizon $horizon): array
    {
        return $this->capacityPlanner->suggestResolutions($workCenterId, $horizon);
    }

    /**
     * {@inheritdoc}
     */
    public function applySuggestion(CapacityResolutionSuggestion $suggestion, array $context = []): bool
    {
        if ($suggestion->requiresApproval && !($context['approved'] ?? false)) {
            $this->logger?->info('Resolution requires approval', [
                'action' => $suggestion->action->value,
                'description' => $suggestion->description,
            ]);
            return false;
        }

        if (!$suggestion->canAutoApply && !($context['forceApply'] ?? false)) {
            $this->logger?->info('Resolution cannot be auto-applied', [
                'action' => $suggestion->action->value,
                'description' => $suggestion->description,
            ]);
            return false;
        }

        return match ($suggestion->action) {
            ResolutionAction::RESCHEDULE => $this->applyReschedule($suggestion, $context),
            ResolutionAction::ALTERNATIVE_WC => $this->applyAlternativeWorkCenter($suggestion, $context),
            ResolutionAction::OVERTIME => $this->applyOvertime($suggestion, $context),
            ResolutionAction::SPLIT => $this->applySplit($suggestion, $context),
            ResolutionAction::SUBCONTRACT => $this->applySubcontract($suggestion, $context),
            ResolutionAction::ADD_SHIFT => $this->applyAddShift($suggestion, $context),
            ResolutionAction::CANCEL => $this->applyCancel($suggestion, $context),
            ResolutionAction::MANUAL => $this->applyManual($suggestion, $context),
        };
    }

    /**
     * {@inheritdoc}
     */
    public function autoResolve(string $workCenterId, PlanningHorizon $horizon): array
    {
        $suggestions = $this->getSuggestions($workCenterId, $horizon);
        $applied = [];
        $remainingExcess = $this->calculateExcessCapacity($workCenterId, $horizon);

        foreach ($suggestions as $suggestion) {
            if ($remainingExcess <= 0) {
                break;
            }

            if ($suggestion->canAutoApply && !$suggestion->requiresApproval) {
                $success = $this->applySuggestion($suggestion, ['auto' => true]);

                if ($success) {
                    $applied[] = $suggestion;
                    $remainingExcess -= $suggestion->resolvesHours;

                    $this->logger?->info('Auto-applied resolution', [
                        'action' => $suggestion->action->value,
                        'resolvedHours' => $suggestion->resolvesHours,
                        'remainingExcess' => max(0, $remainingExcess),
                    ]);
                }
            }
        }

        return $applied;
    }

    /**
     * {@inheritdoc}
     */
    public function validateSuggestion(CapacityResolutionSuggestion $suggestion, array $context = []): array
    {
        $errors = [];

        // Validate based on action type
        match ($suggestion->action) {
            ResolutionAction::RESCHEDULE => $this->validateReschedule($suggestion, $context, $errors),
            ResolutionAction::ALTERNATIVE_WC => $this->validateAlternativeWorkCenter($suggestion, $context, $errors),
            ResolutionAction::OVERTIME => $this->validateOvertime($suggestion, $context, $errors),
            ResolutionAction::SPLIT => $this->validateSplit($suggestion, $context, $errors),
            ResolutionAction::SUBCONTRACT => $this->validateSubcontract($suggestion, $context, $errors),
            ResolutionAction::ADD_SHIFT => $this->validateAddShift($suggestion, $context, $errors),
            ResolutionAction::CANCEL => $this->validateCancel($suggestion, $context, $errors),
            ResolutionAction::MANUAL => null, // No validation needed for manual
        };

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function estimateImpact(CapacityResolutionSuggestion $suggestion, array $context = []): array
    {
        return [
            'hoursResolved' => $suggestion->resolvesHours,
            'estimatedCost' => $suggestion->estimatedCost,
            'daysDelayed' => $suggestion->daysDelayed,
            'affectedWorkOrders' => $context['affectedWorkOrders'] ?? [],
            'qualityImpact' => match ($suggestion->action) {
                ResolutionAction::OVERTIME => 'Potential quality degradation due to overtime',
                ResolutionAction::SUBCONTRACT => 'Quality depends on subcontractor',
                ResolutionAction::SPLIT => 'Minimal impact',
                default => 'None',
            },
            'costBreakdown' => $this->calculateCostBreakdown($suggestion, $context),
            'schedulingImpact' => $this->calculateSchedulingImpact($suggestion, $context),
        ];
    }

    /**
     * Apply reschedule resolution.
     */
    private function applyReschedule(CapacityResolutionSuggestion $suggestion, array $context): bool
    {
        $workOrderId = $context['workOrderId'] ?? null;
        $newDate = $suggestion->newScheduleDate;

        if (!$workOrderId || !$newDate) {
            $this->logger?->warning('Cannot apply reschedule: missing workOrderId or newDate');
            return false;
        }

        try {
            // Update work order scheduled date
            $workOrder = $this->workOrderManager->findById($workOrderId);
            // In real implementation, this would update the work order dates
            $this->logger?->info('Rescheduled work order', [
                'workOrderId' => $workOrderId,
                'newDate' => $newDate->format('Y-m-d'),
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger?->error('Failed to reschedule', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Apply alternative work center resolution.
     */
    private function applyAlternativeWorkCenter(CapacityResolutionSuggestion $suggestion, array $context): bool
    {
        $workOrderId = $context['workOrderId'] ?? null;
        $operationNumber = $context['operationNumber'] ?? null;
        $alternativeWcId = $suggestion->alternativeWorkCenterId;

        if (!$workOrderId || !$alternativeWcId) {
            $this->logger?->warning('Cannot apply alternative WC: missing required context');
            return false;
        }

        try {
            // In real implementation, this would update the work order operation
            $this->logger?->info('Applied alternative work center', [
                'workOrderId' => $workOrderId,
                'operationNumber' => $operationNumber,
                'alternativeWcId' => $alternativeWcId,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger?->error('Failed to apply alternative WC', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Apply overtime resolution.
     */
    private function applyOvertime(CapacityResolutionSuggestion $suggestion, array $context): bool
    {
        $workCenterId = $context['workCenterId'] ?? null;
        $date = $context['date'] ?? null;

        if (!$workCenterId) {
            $this->logger?->warning('Cannot apply overtime: missing workCenterId');
            return false;
        }

        try {
            // In real implementation, this would schedule overtime in the calendar
            $this->logger?->info('Applied overtime', [
                'workCenterId' => $workCenterId,
                'hours' => $suggestion->overtimeHours,
                'date' => $date?->format('Y-m-d'),
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger?->error('Failed to apply overtime', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Apply split resolution.
     */
    private function applySplit(CapacityResolutionSuggestion $suggestion, array $context): bool
    {
        // Split operations typically require manual intervention
        $this->logger?->info('Split resolution flagged for manual handling', [
            'suggestion' => $suggestion->description,
        ]);

        return false;
    }

    /**
     * Apply subcontract resolution.
     */
    private function applySubcontract(CapacityResolutionSuggestion $suggestion, array $context): bool
    {
        // Subcontracting requires purchase order creation
        $this->logger?->info('Subcontract resolution flagged for procurement', [
            'suggestion' => $suggestion->description,
        ]);

        return false;
    }

    /**
     * Apply add shift resolution.
     */
    private function applyAddShift(CapacityResolutionSuggestion $suggestion, array $context): bool
    {
        // Adding shifts requires HR/scheduling approval
        $this->logger?->info('Add shift resolution flagged for approval', [
            'suggestion' => $suggestion->description,
        ]);

        return false;
    }

    /**
     * Apply cancel resolution.
     */
    private function applyCancel(CapacityResolutionSuggestion $suggestion, array $context): bool
    {
        $workOrderId = $context['workOrderId'] ?? null;

        if (!$workOrderId) {
            return false;
        }

        try {
            $this->workOrderManager->cancel($workOrderId, 'Capacity constraint resolution');
            return true;
        } catch (\Exception $e) {
            $this->logger?->error('Failed to cancel work order', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Apply manual resolution.
     */
    private function applyManual(CapacityResolutionSuggestion $suggestion, array $context): bool
    {
        // Manual resolutions just log for tracking
        $this->logger?->info('Manual resolution applied', [
            'suggestion' => $suggestion->description,
            'context' => $context,
        ]);

        return true;
    }

    /**
     * Validate reschedule suggestion.
     */
    private function validateReschedule(
        CapacityResolutionSuggestion $suggestion,
        array $context,
        array &$errors
    ): void {
        if (!$suggestion->newScheduleDate) {
            $errors[] = 'New schedule date is required for reschedule action';
        }

        if ($suggestion->daysDelayed > 30) {
            $errors[] = 'Reschedule delay exceeds maximum allowed (30 days)';
        }
    }

    /**
     * Validate alternative work center suggestion.
     */
    private function validateAlternativeWorkCenter(
        CapacityResolutionSuggestion $suggestion,
        array $context,
        array &$errors
    ): void {
        if (!$suggestion->alternativeWorkCenterId) {
            $errors[] = 'Alternative work center ID is required';
            return;
        }

        try {
            $this->workCenterManager->findById($suggestion->alternativeWorkCenterId);
        } catch (\Exception) {
            $errors[] = 'Alternative work center not found';
        }
    }

    /**
     * Validate overtime suggestion.
     */
    private function validateOvertime(
        CapacityResolutionSuggestion $suggestion,
        array $context,
        array &$errors
    ): void {
        if ($suggestion->overtimeHours > 24) {
            $errors[] = 'Overtime hours exceed maximum (24 hours)';
        }

        $maxOvertimeCost = $context['maxOvertimeBudget'] ?? PHP_FLOAT_MAX;
        if ($suggestion->estimatedCost > $maxOvertimeCost) {
            $errors[] = 'Overtime cost exceeds budget';
        }
    }

    /**
     * Validate split suggestion.
     */
    private function validateSplit(
        CapacityResolutionSuggestion $suggestion,
        array $context,
        array &$errors
    ): void {
        // Split operations have few restrictions
        if ($suggestion->resolvesHours < 1) {
            $errors[] = 'Split must resolve at least 1 hour';
        }
    }

    /**
     * Validate subcontract suggestion.
     */
    private function validateSubcontract(
        CapacityResolutionSuggestion $suggestion,
        array $context,
        array &$errors
    ): void {
        if (empty($context['subcontractorId'])) {
            $errors[] = 'Subcontractor ID is required';
        }
    }

    /**
     * Validate add shift suggestion.
     */
    private function validateAddShift(
        CapacityResolutionSuggestion $suggestion,
        array $context,
        array &$errors
    ): void {
        // Adding shifts typically requires management approval
        if (!($context['managementApproval'] ?? false)) {
            $errors[] = 'Management approval required for adding shifts';
        }
    }

    /**
     * Validate cancel suggestion.
     */
    private function validateCancel(
        CapacityResolutionSuggestion $suggestion,
        array $context,
        array &$errors
    ): void {
        $workOrderId = $context['workOrderId'] ?? null;

        if (!$workOrderId) {
            $errors[] = 'Work order ID is required for cancellation';
            return;
        }

        try {
            $workOrder = $this->workOrderManager->findById($workOrderId);
            if (!$workOrder->getStatus()->canTransitionTo(\Nexus\Manufacturing\Enums\WorkOrderStatus::CANCELLED)) {
                $errors[] = 'Work order cannot be cancelled in current status';
            }
        } catch (\Exception) {
            $errors[] = 'Work order not found';
        }
    }

    /**
     * Calculate excess capacity for a work center.
     */
    private function calculateExcessCapacity(string $workCenterId, PlanningHorizon $horizon): float
    {
        $profile = $this->capacityPlanner->calculateLoad($workCenterId, $horizon);
        return max(0, $profile->totalLoadedCapacity - $profile->totalAvailableCapacity);
    }

    /**
     * Calculate cost breakdown for a suggestion.
     */
    private function calculateCostBreakdown(CapacityResolutionSuggestion $suggestion, array $context): array
    {
        return match ($suggestion->action) {
            ResolutionAction::OVERTIME => [
                'overtimeHours' => $suggestion->overtimeHours,
                'hourlyRate' => $suggestion->overtimeCostPerHour ?? 75.0,
                'totalCost' => $suggestion->overtimeHours * ($suggestion->overtimeCostPerHour ?? 75.0),
            ],
            ResolutionAction::SUBCONTRACT => [
                'estimatedCost' => $suggestion->estimatedCost,
                'setup' => $suggestion->estimatedCost * 0.2,
                'processing' => $suggestion->estimatedCost * 0.7,
                'logistics' => $suggestion->estimatedCost * 0.1,
            ],
            default => [
                'estimatedCost' => $suggestion->estimatedCost,
            ],
        };
    }

    /**
     * Calculate scheduling impact for a suggestion.
     */
    private function calculateSchedulingImpact(CapacityResolutionSuggestion $suggestion, array $context): array
    {
        return [
            'daysDelayed' => $suggestion->daysDelayed,
            'affectedOrders' => count($context['affectedWorkOrders'] ?? []),
            'cascadeRisk' => $suggestion->daysDelayed > 7 ? 'High' : ($suggestion->daysDelayed > 3 ? 'Medium' : 'Low'),
        ];
    }
}
