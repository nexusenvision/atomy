<?php

declare(strict_types=1);

namespace Nexus\EventStream\Services;

use Nexus\EventStream\Contracts\EventUpcasterInterface;
use Nexus\EventStream\Contracts\UpcasterInterface;
use Nexus\EventStream\Exceptions\UpcasterFailedException;

/**
 * Default Event Upcaster
 *
 * Framework-agnostic implementation of event schema migration orchestration.
 * Chains multiple individual upcasters to transform events from any version
 * to the latest version.
 *
 * Features:
 * - Chain of Responsibility pattern for composable transformations
 * - Fail-fast error handling with detailed context
 * - Version gap detection to prevent invalid migrations
 * - Immutable transformations (original events never modified)
 *
 * Example Usage:
 * ```php
 * $upcaster = new DefaultEventUpcaster();
 * $upcaster
 *     ->registerUpcaster(new AccountCreatedV1ToV2Upcaster())
 *     ->registerUpcaster(new AccountCreatedV2ToV3Upcaster());
 *
 * // Upcast from v1 to v3
 * $result = $upcaster->upcastEvent('AccountCreated', 1, $oldEventData);
 * // $result['version'] === 3
 * // $result['data'] === $upcastedData
 * ```
 *
 * Requirements satisfied:
 * - MAI-EVS-7810: Event schema migration orchestration
 * - REL-EVS-7415: Fail-fast upcasting with individual testability
 * - ARC-EVS-7017: Composable, immutable transformation chain
 *
 * @package Nexus\EventStream\Services
 */
final class DefaultEventUpcaster implements EventUpcasterInterface
{
    /**
     * @var array<string, UpcasterInterface[]>
     */
    private array $upcasters = [];

    public function upcastEvent(string $eventType, int $currentVersion, array $eventData): array
    {
        $upcastersForEvent = $this->getUpcastersForEventType($eventType);

        if (empty($upcastersForEvent)) {
            return ['version' => $currentVersion, 'data' => $eventData];
        }

        $version = $currentVersion;
        $data = $eventData;
        $latestVersion = $this->getLatestVersion($eventType);

        // Apply upcasters in sequence
        while ($version < $latestVersion) {
            $nextUpcaster = null;

            // Find upcaster that transforms from current version
            foreach ($upcastersForEvent as $upcaster) {
                if ($upcaster->supports($eventType, $version)) {
                    $nextUpcaster = $upcaster;
                    break;
                }
            }

            // No upcaster found for current version → version gap
            if ($nextUpcaster === null) {
                throw UpcasterFailedException::versionGap($eventType, $version, $version + 1);
            }

            $targetVersion = $nextUpcaster->getTargetVersion();

            try {
                $data = $nextUpcaster->upcast($data);
                $version = $targetVersion;
            } catch (\Throwable $e) {
                throw new UpcasterFailedException(
                    $eventType,
                    $version,
                    $targetVersion,
                    $e->getMessage(),
                    '',
                    0,
                    $e
                );
            }
        }

        return ['version' => $version, 'data' => $data];
    }

    public function registerUpcaster(UpcasterInterface $upcaster): self
    {
        // Detect which event types this upcaster supports by probing common versions
        // We probe versions 1-10 as a reasonable range for event schema versioning
        $eventTypeDetected = false;

        // Common event type patterns to check (in production, this could be externalized)
        $commonEventTypes = [
            'AccountCreated', 'AccountCredited', 'AccountDebited',
            'PaymentReceived', 'PaymentFailed', 'InvoiceCreated',
            'StockReserved', 'StockAdded', 'StockShipped'
        ];

        foreach ($commonEventTypes as $eventType) {
            for ($version = 1; $version <= 10; $version++) {
                if ($upcaster->supports($eventType, $version)) {
                    if (!isset($this->upcasters[$eventType])) {
                        $this->upcasters[$eventType] = [];
                    }
                    $this->upcasters[$eventType][] = $upcaster;
                    $eventTypeDetected = true;
                    break; // Move to next event type
                }
            }
        }

        // If no common event type matched, store in a special '__unknown__' key
        // This allows custom event types to still work via getUpcastersForEventType()
        if (!$eventTypeDetected) {
            if (!isset($this->upcasters['__unknown__'])) {
                $this->upcasters['__unknown__'] = [];
            }
            $this->upcasters['__unknown__'][] = $upcaster;
        }

        return $this;
    }

    public function getUpcastersForEventType(string $eventType): array
    {
        // Direct lookup for registered event types
        $upcasters = $this->upcasters[$eventType] ?? [];

        // Also check unknown upcasters for this specific event type
        if (isset($this->upcasters['__unknown__'])) {
            foreach ($this->upcasters['__unknown__'] as $upcaster) {
                // Probe versions 1-10 for unknown upcasters
                for ($version = 1; $version <= 10; $version++) {
                    if ($upcaster->supports($eventType, $version)) {
                        $upcasters[] = $upcaster;
                        break;
                    }
                }
            }
        }

        // Sort by target version
        usort($upcasters, fn($a, $b) => $a->getTargetVersion() <=> $b->getTargetVersion());

        return $upcasters;
    }

    public function getLatestVersion(string $eventType): ?int
    {
        $upcasters = $this->getUpcastersForEventType($eventType);

        if (empty($upcasters)) {
            return null;
        }

        $latestVersion = 0;
        foreach ($upcasters as $upcaster) {
            $targetVersion = $upcaster->getTargetVersion();
            if ($targetVersion > $latestVersion) {
                $latestVersion = $targetVersion;
            }
        }

        return $latestVersion;
    }
}
