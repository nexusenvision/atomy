<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\CapacityPlannerInterface;
use Nexus\Manufacturing\Contracts\WorkCenterManagerInterface;
use Nexus\Manufacturing\Contracts\RoutingManagerInterface;
use Nexus\Manufacturing\Contracts\WorkOrderRepositoryInterface;
use Nexus\Manufacturing\Contracts\PlannedOrderRepositoryInterface;
use Nexus\Manufacturing\Enums\WorkOrderStatus;
use Nexus\Manufacturing\Enums\ResolutionAction;
use Nexus\Manufacturing\Exceptions\CapacityExceededException;
use Nexus\Manufacturing\ValueObjects\PlanningHorizon;
use Nexus\Manufacturing\ValueObjects\CapacityProfile;
use Nexus\Manufacturing\ValueObjects\CapacityPeriod;
use Nexus\Manufacturing\ValueObjects\CapacityLoad;
use Nexus\Manufacturing\ValueObjects\CapacityResolutionSuggestion;

/**
 * Capacity Planner implementation.
 *
 * Calculates work center capacity loads and provides resolution suggestions.
 */
final readonly class CapacityPlanner implements CapacityPlannerInterface
{
    public function __construct(
        private WorkCenterManagerInterface $workCenterManager,
        private RoutingManagerInterface $routingManager,
        private WorkOrderRepositoryInterface $workOrderRepository,
        private PlannedOrderRepositoryInterface $plannedOrderRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function calculateLoad(string $workCenterId, PlanningHorizon $horizon): CapacityProfile
    {
        $workCenter = $this->workCenterManager->findById($workCenterId);
        $periods = [];
        $totalAvailable = 0.0;
        $totalLoaded = 0.0;

        // Create periods based on horizon buckets
        foreach ($horizon->getBuckets() as $bucket) {
            $periodStart = $bucket['start'];
            $periodEnd = $bucket['end'];

            // Get available hours for this period
            $availableHours = $this->workCenterManager->getAvailableHoursForPeriod(
                $workCenterId,
                $periodStart,
                $periodEnd
            );

            // Get loads for this period
            $loads = $this->getLoadsForPeriod($workCenterId, $periodStart, $periodEnd);

            $loadedHours = array_sum(array_map(fn (CapacityLoad $l) => $l->hours, $loads));

            $periods[] = new CapacityPeriod(
                periodStart: $periodStart,
                periodEnd: $periodEnd,
                availableHours: $availableHours,
                loadedHours: $loadedHours,
                loads: $loads,
            );

            $totalAvailable += $availableHours;
            $totalLoaded += $loadedHours;
        }

        return new CapacityProfile(
            workCenterId: $workCenterId,
            horizon: $horizon,
            periods: $periods,
            totalAvailableCapacity: $totalAvailable,
            totalLoadedCapacity: $totalLoaded,
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function calculateLoadForProduct(string $productId, float $quantity, PlanningHorizon $horizon): array
    {
        $profiles = [];

        try {
            $capacityRequirements = $this->routingManager->calculateCapacityRequirement($productId, $quantity);

            foreach ($capacityRequirements as $requirement) {
                $workCenterId = $requirement['workCenterId'];
                $profile = $this->calculateLoad($workCenterId, $horizon);

                // Add the new product load to assess impact
                $additionalHours = $requirement['totalHours'];

                $profiles[$workCenterId] = [
                    'profile' => $profile,
                    'additionalHours' => $additionalHours,
                    'newTotalLoad' => $profile->totalLoadedCapacity + $additionalHours,
                    'newUtilization' => $profile->totalAvailableCapacity > 0
                        ? (($profile->totalLoadedCapacity + $additionalHours) / $profile->totalAvailableCapacity) * 100
                        : 0,
                    'isOverloaded' => ($profile->totalLoadedCapacity + $additionalHours) > $profile->totalAvailableCapacity,
                ];
            }
        } catch (\Exception) {
            // No routing for product
        }

        return $profiles;
    }

    /**
     * {@inheritdoc}
     */
    public function getOverloadedWorkCenters(PlanningHorizon $horizon): array
    {
        $overloaded = [];
        $workCenters = $this->workCenterManager->findActive();

        foreach ($workCenters as $workCenter) {
            $profile = $this->calculateLoad($workCenter->getId(), $horizon);

            if ($profile->isOverloaded()) {
                $overloaded[] = [
                    'workCenterId' => $workCenter->getId(),
                    'workCenterCode' => $workCenter->getCode(),
                    'profile' => $profile,
                    'utilization' => $profile->getUtilization(),
                    'excessHours' => $profile->getExcessLoad(),
                    'overloadedPeriods' => count($profile->getOverloadedPeriods()),
                ];
            }
        }

        // Sort by excess load (highest first)
        usort($overloaded, fn ($a, $b) => $b['excessHours'] <=> $a['excessHours']);

        return $overloaded;
    }

    /**
     * {@inheritdoc}
     */
    public function suggestResolutions(string $workCenterId, PlanningHorizon $horizon): array
    {
        $profile = $this->calculateLoad($workCenterId, $horizon);

        if (!$profile->isOverloaded()) {
            return [];
        }

        $suggestions = [];
        $excessHours = $profile->getExcessLoad();
        $workCenter = $this->workCenterManager->findById($workCenterId);

        // Strategy 1: Alternative work centers
        $alternatives = $this->workCenterManager->getAlternatives($workCenterId);
        foreach ($alternatives as $alternative) {
            $altProfile = $this->calculateLoad($alternative->getId(), $horizon);

            if ($altProfile->getAvailableCapacity() > 0) {
                $transferableHours = min($excessHours, $altProfile->getAvailableCapacity());

                $suggestions[] = CapacityResolutionSuggestion::alternativeWorkCenter(
                    alternativeWorkCenterId: $alternative->getId(),
                    resolvesHours: $transferableHours,
                    additionalCost: 0.0 // Could calculate based on rate differential
                );
            }
        }

        // Strategy 2: Overtime
        $maxOvertimeHoursPerDay = 4.0;
        $daysInHorizon = $horizon->getTotalDays();
        $workingDays = (int) ($daysInHorizon * ($workCenter->getDaysPerWeek() / 7));
        $maxOvertime = $maxOvertimeHoursPerDay * $workingDays;
        $overtimeHours = min($excessHours, $maxOvertime);

        if ($overtimeHours > 0) {
            $overtimeCostPerHour = 75.0; // Could be configurable
            $suggestions[] = CapacityResolutionSuggestion::overtime(
                overtimeHours: $overtimeHours,
                overtimeCostPerHour: $overtimeCostPerHour
            );
        }

        // Strategy 3: Reschedule to periods with capacity
        foreach ($profile->getOverloadedPeriods() as $overloadedPeriod) {
            // Find first period with available capacity
            foreach ($profile->periods as $period) {
                if (!$period->isOverloaded() && $period->periodStart > $overloadedPeriod->periodStart) {
                    $reschedulableHours = min(
                        $overloadedPeriod->getExcessHours(),
                        $period->getRemainingHours()
                    );

                    if ($reschedulableHours > 0) {
                        $daysDelayed = (int) $overloadedPeriod->periodStart->diff($period->periodStart)->days;

                        $suggestions[] = CapacityResolutionSuggestion::reschedule(
                            newDate: $period->periodStart,
                            resolvesHours: $reschedulableHours,
                            daysDelayed: $daysDelayed
                        );
                    }
                    break;
                }
            }
        }

        // Strategy 4: Split operations
        $suggestions[] = new CapacityResolutionSuggestion(
            action: ResolutionAction::SPLIT,
            description: 'Split large operations across multiple periods',
            resolvesHours: $excessHours * 0.5, // Estimate 50% resolution
            priority: ResolutionAction::SPLIT->getDefaultPriority(),
            requiresApproval: true,
            canAutoApply: false,
            reason: 'Splitting operations allows parallel processing',
        );

        // Strategy 5: Add shift
        $newShiftHours = $workCenter->getHoursPerDay() * $workingDays;
        if ($newShiftHours > 0 && $excessHours > $maxOvertime) {
            $suggestions[] = new CapacityResolutionSuggestion(
                action: ResolutionAction::ADD_SHIFT,
                description: "Add additional shift (adds {$newShiftHours} hours)",
                resolvesHours: $newShiftHours,
                priority: ResolutionAction::ADD_SHIFT->getDefaultPriority(),
                estimatedCost: $newShiftHours * 50.0, // Estimate cost per hour
                requiresApproval: true,
                canAutoApply: false,
                reason: 'Additional shift can fully resolve capacity shortage',
            );
        }

        // Sort by priority (lowest number = highest priority)
        usort($suggestions, fn ($a, $b) => $a->priority <=> $b->priority);

        return $suggestions;
    }

    /**
     * {@inheritdoc}
     */
    public function checkCapacityAvailability(
        string $workCenterId,
        float $requiredHours,
        \DateTimeImmutable $date
    ): bool {
        $availableHours = $this->workCenterManager->getAvailableHours($workCenterId, $date);

        // Get existing load for the date
        $horizon = new PlanningHorizon(
            startDate: $date,
            endDate: $date->modify('+1 day'),
        );

        $loads = $this->getLoadsForPeriod($workCenterId, $date, $date->modify('+1 day'));
        $loadedHours = array_sum(array_map(fn (CapacityLoad $l) => $l->hours, $loads));

        $remainingHours = $availableHours - $loadedHours;

        return $remainingHours >= $requiredHours;
    }

    /**
     * {@inheritdoc}
     */
    public function findAvailableSlot(
        string $workCenterId,
        float $requiredHours,
        \DateTimeImmutable $earliestDate,
        PlanningHorizon $horizon
    ): ?\DateTimeImmutable {
        $current = max($earliestDate, $horizon->startDate);

        while ($current <= $horizon->endDate) {
            if ($this->checkCapacityAvailability($workCenterId, $requiredHours, $current)) {
                return $current;
            }
            $current = $current->modify('+1 day');
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function roughCutCapacityPlan(array $masterSchedule, PlanningHorizon $horizon): array
    {
        $rccp = [];

        foreach ($masterSchedule as $item) {
            $productId = $item['productId'];
            $quantity = $item['quantity'];
            $dueDate = new \DateTimeImmutable($item['dueDate']);

            $loads = $this->calculateLoadForProduct($productId, $quantity, $horizon);

            foreach ($loads as $workCenterId => $loadInfo) {
                if (!isset($rccp[$workCenterId])) {
                    $rccp[$workCenterId] = [
                        'workCenterId' => $workCenterId,
                        'totalAvailable' => $loadInfo['profile']->totalAvailableCapacity,
                        'items' => [],
                        'totalLoad' => 0.0,
                    ];
                }

                $rccp[$workCenterId]['items'][] = [
                    'productId' => $productId,
                    'quantity' => $quantity,
                    'dueDate' => $dueDate->format('Y-m-d'),
                    'hours' => $loadInfo['additionalHours'],
                ];

                $rccp[$workCenterId]['totalLoad'] += $loadInfo['additionalHours'];
            }
        }

        // Calculate utilization for each work center
        foreach ($rccp as $workCenterId => &$data) {
            $data['utilization'] = $data['totalAvailable'] > 0
                ? ($data['totalLoad'] / $data['totalAvailable']) * 100
                : 0;
            $data['isOverloaded'] = $data['totalLoad'] > $data['totalAvailable'];
        }

        return array_values($rccp);
    }

    /**
     * Get capacity loads for a work center in a period.
     *
     * @return array<CapacityLoad>
     */
    private function getLoadsForPeriod(
        string $workCenterId,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): array {
        $loads = [];

        // Get loads from firm work orders
        $workOrders = $this->workOrderRepository->findByWorkCenterAndDateRange(
            $workCenterId,
            $periodStart,
            $periodEnd,
            [WorkOrderStatus::PLANNED, WorkOrderStatus::RELEASED, WorkOrderStatus::IN_PROGRESS]
        );

        foreach ($workOrders as $workOrder) {
            foreach ($workOrder->getLines() as $line) {
                if ($line->workCenterId === $workCenterId && $line->isOperation()) {
                    $loads[] = new CapacityLoad(
                        sourceId: $workOrder->getId(),
                        sourceType: 'work_order',
                        workCenterId: $workCenterId,
                        hours: $line->plannedSetupHours + $line->plannedRunHours,
                        setupHours: $line->plannedSetupHours,
                        runHours: $line->plannedRunHours,
                        loadDate: $workOrder->getPlannedStartDate(),
                        operationNumber: $line->operationNumber,
                        productId: $workOrder->getProductId(),
                        quantity: $workOrder->getQuantity(),
                    );
                }
            }
        }

        // Get loads from planned orders
        $plannedOrders = $this->plannedOrderRepository->findByWorkCenterAndDateRange(
            $workCenterId,
            $periodStart,
            $periodEnd
        );

        foreach ($plannedOrders as $plannedOrder) {
            $loads[] = new CapacityLoad(
                sourceId: $plannedOrder->id ?? uniqid(),
                sourceType: 'planned_order',
                workCenterId: $workCenterId,
                hours: $plannedOrder->hours ?? 0.0,
                setupHours: $plannedOrder->setupHours ?? 0.0,
                runHours: $plannedOrder->runHours ?? 0.0,
                loadDate: $plannedOrder->startDate,
                productId: $plannedOrder->productId,
                quantity: $plannedOrder->quantity,
            );
        }

        return $loads;
    }
}
