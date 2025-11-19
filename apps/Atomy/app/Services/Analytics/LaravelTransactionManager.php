<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Nexus\Analytics\Core\Contracts\TransactionManagerInterface;
use Nexus\Analytics\Exceptions\TransactionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Laravel implementation of transaction manager
 * 
 * Satisfies: BUS-ANA-0136, REL-ANA-0414 (ACID compliance)
 */
final class LaravelTransactionManager implements TransactionManagerInterface
{
    /**
     * {@inheritdoc}
     */
    public function executeInTransaction(callable $callback): mixed
    {
        try {
            return DB::transaction($callback);
        } catch (\Throwable $e) {
            throw new TransactionException(
                'execution',
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function begin(): void
    {
        try {
            DB::beginTransaction();
        } catch (\Throwable $e) {
            throw new TransactionException(
                'begin',
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): void
    {
        try {
            DB::commit();
        } catch (\Throwable $e) {
            throw new TransactionException(
                'commit',
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(): void
    {
        try {
            DB::rollBack();
        } catch (\Throwable $e) {
            throw new TransactionException(
                'rollback',
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function compensate(string $queryId, array $failureContext): void
    {
        // BUS-ANA-0138: Failed queries MUST use compensation actions for reversal
        // Log the failure for compensation tracking
        Log::warning('Analytics query failed, compensation required', [
            'query_id' => $queryId,
            'context' => $failureContext,
        ]);

        // In a real implementation, this would:
        // 1. Record compensation needed in a dedicated table
        // 2. Trigger compensation workflow
        // 3. Notify relevant parties
        // 4. Potentially revert any partial changes
    }
}
