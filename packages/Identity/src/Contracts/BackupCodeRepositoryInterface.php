<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

use Nexus\Identity\ValueObjects\BackupCode;
use Nexus\Identity\ValueObjects\BackupCodeSet;

/**
 * Repository contract for backup code persistence.
 *
 * Manages storage and retrieval of backup codes for account recovery.
 */
interface BackupCodeRepositoryInterface
{
    /**
     * Find all backup codes for an enrollment.
     *
     * @param string $enrollmentId The enrollment identifier
     * @return BackupCodeSet The set of backup codes
     */
    public function findByEnrollmentId(string $enrollmentId): BackupCodeSet;

    /**
     * Find a specific backup code by its hash.
     *
     * Used for verification during authentication.
     *
     * @param string $enrollmentId The enrollment identifier
     * @param string $hash The Argon2id hash of the code
     * @return BackupCode|null The code or null if not found
     */
    public function findByHash(string $enrollmentId, string $hash): ?BackupCode;

    /**
     * Save a set of backup codes.
     *
     * Replaces all existing codes for the enrollment.
     *
     * @param string $enrollmentId The enrollment identifier
     * @param BackupCodeSet $codeSet The set of backup codes
     * @return bool True if saved successfully
     */
    public function saveSet(string $enrollmentId, BackupCodeSet $codeSet): bool;

    /**
     * Mark a backup code as consumed.
     *
     * @param string $enrollmentId The enrollment identifier
     * @param string $codeHash The hash of the code to consume
     * @return bool True if marked successfully, false if not found
     */
    public function markAsConsumed(string $enrollmentId, string $codeHash): bool;

    /**
     * Count remaining (unconsumed) codes for an enrollment.
     *
     * @param string $enrollmentId The enrollment identifier
     * @return int Number of remaining codes
     */
    public function countRemaining(string $enrollmentId): int;

    /**
     * Delete all backup codes for an enrollment.
     *
     * @param string $enrollmentId The enrollment identifier
     * @return bool True if deleted
     */
    public function deleteByEnrollmentId(string $enrollmentId): bool;

    /**
     * Check if regeneration should be triggered.
     *
     * Returns true if remaining codes are below threshold (typically ≤2).
     *
     * @param string $enrollmentId The enrollment identifier
     * @param int $threshold The threshold for triggering regeneration
     * @return bool True if regeneration should be triggered
     */
    public function shouldTriggerRegeneration(string $enrollmentId, int $threshold = 2): bool;
}
