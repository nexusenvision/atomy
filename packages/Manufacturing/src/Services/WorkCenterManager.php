<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\WorkCenterManagerInterface;
use Nexus\Manufacturing\Contracts\WorkCenterRepositoryInterface;
use Nexus\Manufacturing\Contracts\WorkCenterInterface;
use Nexus\Manufacturing\Enums\WorkCenterType;
use Nexus\Manufacturing\Exceptions\WorkCenterNotFoundException;

/**
 * Work Center Manager implementation.
 *
 * Manages work center master data and capacity calculations.
 */
final readonly class WorkCenterManager implements WorkCenterManagerInterface
{
    public function __construct(
        private WorkCenterRepositoryInterface $repository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function create(
        string $code,
        string $name,
        WorkCenterType $type,
        float $capacityPerHour,
        float $hoursPerDay = 8.0,
        int $daysPerWeek = 5,
        float $efficiency = 100.0,
        float $utilization = 100.0
    ): WorkCenterInterface {
        return $this->repository->create([
            'code' => $code,
            'name' => $name,
            'type' => $type->value,
            'capacityPerHour' => $capacityPerHour,
            'hoursPerDay' => $hoursPerDay,
            'daysPerWeek' => $daysPerWeek,
            'efficiency' => $efficiency,
            'utilization' => $utilization,
            'isActive' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): WorkCenterInterface
    {
        $workCenter = $this->repository->findById($id);

        if ($workCenter === null) {
            throw WorkCenterNotFoundException::withId($id);
        }

        return $workCenter;
    }

    /**
     * {@inheritdoc}
     */
    public function findByCode(string $code): WorkCenterInterface
    {
        $workCenter = $this->repository->findByCode($code);

        if ($workCenter === null) {
            throw WorkCenterNotFoundException::withCode($code);
        }

        return $workCenter;
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $id, array $data): WorkCenterInterface
    {
        $this->findById($id);

        return $this->repository->update($id, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(string $id): WorkCenterInterface
    {
        $this->findById($id);

        return $this->repository->update($id, ['isActive' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function activate(string $id): WorkCenterInterface
    {
        $this->findById($id);

        return $this->repository->update($id, ['isActive' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateDailyCapacity(string $id): float
    {
        $workCenter = $this->findById($id);

        return $workCenter->getCapacityPerHour()
            * $workCenter->getHoursPerDay()
            * ($workCenter->getEfficiency() / 100)
            * ($workCenter->getUtilization() / 100);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateWeeklyCapacity(string $id): float
    {
        $workCenter = $this->findById($id);
        $dailyCapacity = $this->calculateDailyCapacity($id);

        return $dailyCapacity * $workCenter->getDaysPerWeek();
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableHours(string $id, \DateTimeImmutable $date): float
    {
        $workCenter = $this->findById($id);

        // Check if date is a working day
        $dayOfWeek = (int) $date->format('N'); // 1 (Monday) to 7 (Sunday)
        $daysPerWeek = $workCenter->getDaysPerWeek();

        // Assume work days are Monday to Friday for 5-day week, etc.
        if ($dayOfWeek > $daysPerWeek) {
            return 0.0;
        }

        // Check for holidays or special closures
        $closures = $this->repository->getClosures($id, $date);
        if (count($closures) > 0) {
            return 0.0;
        }

        // Apply efficiency and utilization
        return $workCenter->getHoursPerDay()
            * ($workCenter->getEfficiency() / 100)
            * ($workCenter->getUtilization() / 100);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableHoursForPeriod(string $id, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): float
    {
        $totalHours = 0.0;
        $current = $startDate;

        while ($current <= $endDate) {
            $totalHours += $this->getAvailableHours($id, $current);
            $current = $current->modify('+1 day');
        }

        return $totalHours;
    }

    /**
     * {@inheritdoc}
     */
    public function addClosure(string $id, \DateTimeImmutable $date, string $reason, float $hoursUnavailable = 0.0): void
    {
        $this->findById($id);

        $this->repository->addClosure($id, [
            'date' => $date->format('Y-m-d'),
            'reason' => $reason,
            'hoursUnavailable' => $hoursUnavailable,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function removeClosure(string $id, \DateTimeImmutable $date): void
    {
        $this->findById($id);

        $this->repository->removeClosure($id, $date);
    }

    /**
     * {@inheritdoc}
     */
    public function setAlternatives(string $id, array $alternativeIds): WorkCenterInterface
    {
        $this->findById($id);

        // Validate all alternatives exist
        foreach ($alternativeIds as $altId) {
            $this->findById($altId);
        }

        return $this->repository->update($id, ['alternativeIds' => $alternativeIds]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlternatives(string $id): array
    {
        $workCenter = $this->findById($id);

        $alternativeIds = $workCenter->getAlternativeIds();
        $alternatives = [];

        foreach ($alternativeIds as $altId) {
            try {
                $alt = $this->findById($altId);
                if ($alt->isActive()) {
                    $alternatives[] = $alt;
                }
            } catch (WorkCenterNotFoundException) {
                // Alternative no longer exists
            }
        }

        return $alternatives;
    }

    /**
     * {@inheritdoc}
     */
    public function findByType(WorkCenterType $type): array
    {
        return $this->repository->findByType($type);
    }

    /**
     * {@inheritdoc}
     */
    public function findActive(): array
    {
        return $this->repository->findActive();
    }
}
