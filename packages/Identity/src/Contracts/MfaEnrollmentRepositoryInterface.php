<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

use Nexus\Identity\ValueObjects\MfaMethod;

/**
 * Repository contract for MFA enrollment persistence.
 *
 * Handles CRUD operations for MFA enrollments and specialized queries
 * for multi-factor authentication management.
 */
interface MfaEnrollmentRepositoryInterface
{
    /**
     * Find an enrollment by its unique identifier.
     *
     * @param string $enrollmentId The ULID enrollment identifier
     * @return MfaEnrollmentInterface|null The enrollment or null if not found
     */
    public function findById(string $enrollmentId): ?MfaEnrollmentInterface;

    /**
     * Find all enrollments for a specific user.
     *
     * @param string $userId The user identifier
     * @return array<MfaEnrollmentInterface> Array of enrollments
     */
    public function findByUserId(string $userId): array;

    /**
     * Find all active enrollments for a user.
     *
     * Active enrollments are those that are verified and not deleted.
     *
     * @param string $userId The user identifier
     * @return array<MfaEnrollmentInterface> Array of active enrollments
     */
    public function findActiveByUserId(string $userId): array;

    /**
     * Find enrollment by user and method.
     *
     * @param string $userId The user identifier
     * @param MfaMethod $method The MFA method
     * @return MfaEnrollmentInterface|null The enrollment or null if not found
     */
    public function findByUserAndMethod(string $userId, MfaMethod $method): ?MfaEnrollmentInterface;

    /**
     * Find the primary enrollment for a user.
     *
     * The primary enrollment is used as the default MFA method.
     *
     * @param string $userId The user identifier
     * @return MfaEnrollmentInterface|null The primary enrollment or null if none set
     */
    public function findPrimaryByUserId(string $userId): ?MfaEnrollmentInterface;

    /**
     * Save an enrollment (create or update).
     *
     * @param MfaEnrollmentInterface $enrollment The enrollment to save
     * @return MfaEnrollmentInterface The saved enrollment
     */
    public function save(MfaEnrollmentInterface $enrollment): MfaEnrollmentInterface;

    /**
     * Delete an enrollment.
     *
     * @param string $enrollmentId The enrollment identifier
     * @return bool True if deleted, false if not found
     */
    public function delete(string $enrollmentId): bool;

    /**
     * Count active enrollments for a user.
     *
     * @param string $userId The user identifier
     * @return int Number of active enrollments
     */
    public function countActiveByUserId(string $userId): int;

    /**
     * Check if user has any verified MFA enrollment.
     *
     * @param string $userId The user identifier
     * @return bool True if user has at least one verified enrollment
     */
    public function hasVerifiedEnrollment(string $userId): bool;

    /**
     * Set an enrollment as primary and unset others.
     *
     * @param string $enrollmentId The enrollment to set as primary
     * @return bool True if successful
     */
    public function setPrimary(string $enrollmentId): bool;

    /**
     * Find all enrollments that need verification reminder.
     *
     * Returns unverified enrollments older than a specified time.
     *
     * @param int $hoursOld Minimum age in hours
     * @return array<MfaEnrollmentInterface> Enrollments needing reminder
     */
    public function findUnverifiedOlderThan(int $hoursOld): array;
}
