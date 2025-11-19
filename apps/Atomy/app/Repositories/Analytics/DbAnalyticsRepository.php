<?php

declare(strict_types=1);

namespace App\Repositories\Analytics;

use App\Models\Analytics\AnalyticsQueryDefinition;
use App\Models\Analytics\AnalyticsQueryResult;
use App\Models\Analytics\AnalyticsInstance;
use Nexus\Analytics\Contracts\AnalyticsRepositoryInterface;
use Illuminate\Support\Str;

/**
 * Database implementation of Analytics repository
 */
final class DbAnalyticsRepository implements AnalyticsRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function storeQueryDefinition(array $data): string
    {
        $query = AnalyticsQueryDefinition::create([
            'id' => $data['id'] ?? Str::uuid()->toString(),
            'name' => $data['name'],
            'type' => $data['type'] ?? 'generic',
            'description' => $data['description'] ?? null,
            'model_type' => $data['model_type'] ?? null,
            'model_id' => $data['model_id'] ?? null,
            'parameters' => $data['parameters'] ?? [],
            'guards' => $data['guards'] ?? [],
            'data_sources' => $data['data_sources'] ?? [],
            'requires_transaction' => $data['requires_transaction'] ?? true,
            'timeout' => $data['timeout'] ?? 300,
            'supports_parallel_execution' => $data['supports_parallel_execution'] ?? false,
            'created_by' => $data['created_by'] ?? null,
        ]);

        return $query->id;
    }

    /**
     * {@inheritdoc}
     */
    public function findQueryDefinition(string $id): ?array
    {
        $query = AnalyticsQueryDefinition::find($id);

        if ($query === null) {
            return null;
        }

        return [
            'id' => $query->id,
            'name' => $query->name,
            'type' => $query->type,
            'description' => $query->description,
            'model_type' => $query->model_type,
            'model_id' => $query->model_id,
            'parameters' => $query->parameters,
            'guards' => $query->guards,
            'data_sources' => $query->data_sources,
            'requires_transaction' => $query->requires_transaction,
            'timeout' => $query->timeout,
            'supports_parallel_execution' => $query->supports_parallel_execution,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findQueryDefinitionsByModel(string $modelType, string $modelId): array
    {
        $queries = AnalyticsQueryDefinition::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->get();

        return $queries->map(fn($query) => [
            'id' => $query->id,
            'name' => $query->name,
            'type' => $query->type,
            'description' => $query->description,
            'parameters' => $query->parameters,
            'guards' => $query->guards,
            'data_sources' => $query->data_sources,
            'requires_transaction' => $query->requires_transaction,
            'timeout' => $query->timeout,
            'supports_parallel_execution' => $query->supports_parallel_execution,
        ])->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function storeQueryResult(array $data): string
    {
        $result = AnalyticsQueryResult::create([
            'id' => Str::uuid()->toString(),
            'query_id' => $data['query_id'],
            'query_name' => $data['query_name'] ?? 'unknown',
            'model_type' => $data['model_type'],
            'model_id' => $data['model_id'],
            'executed_by' => $data['executed_by'] ?? null,
            'executed_at' => $data['executed_at'] ?? now(),
            'duration_ms' => $data['duration_ms'] ?? 0,
            'is_successful' => $data['is_successful'] ?? true,
            'error' => $data['error'] ?? null,
            'result_data' => $data['result_data'] ?? [],
            'metadata' => $data['metadata'] ?? [],
            'tenant_id' => $data['tenant_id'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
        ]);

        return $result->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getHistory(string $entityType, string $entityId, int $limit = 50): array
    {
        $results = AnalyticsQueryResult::where('model_type', $entityType)
            ->where('model_id', $entityId)
            ->orderBy('executed_at', 'desc')
            ->limit($limit)
            ->get();

        return $results->map(fn($result) => [
            'id' => $result->id,
            'query_id' => $result->query_id,
            'query_name' => $result->query_name,
            'executed_by' => $result->executed_by,
            'executed_at' => $result->executed_at->toIso8601String(),
            'duration_ms' => $result->duration_ms,
            'is_successful' => $result->is_successful,
            'error' => $result->error,
            'result_data' => $result->result_data,
        ])->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function storeAnalyticsInstance(array $data): string
    {
        $instance = AnalyticsInstance::create([
            'id' => Str::uuid()->toString(),
            'model_type' => $data['model_type'],
            'model_id' => $data['model_id'],
            'configuration' => $data['configuration'] ?? [],
            'created_by' => $data['created_by'] ?? null,
        ]);

        return $instance->id;
    }

    /**
     * {@inheritdoc}
     */
    public function findAnalyticsInstance(string $modelType, string $modelId): ?array
    {
        $instance = AnalyticsInstance::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->first();

        if ($instance === null) {
            return null;
        }

        return [
            'id' => $instance->id,
            'model_type' => $instance->model_type,
            'model_id' => $instance->model_id,
            'configuration' => $instance->configuration,
            'last_query_at' => $instance->last_query_at?->toIso8601String(),
            'total_queries' => $instance->total_queries,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function updateAnalyticsInstance(string $id, array $data): bool
    {
        $instance = AnalyticsInstance::find($id);

        if ($instance === null) {
            return false;
        }

        $instance->update($data);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteQueryDefinition(string $id): bool
    {
        $query = AnalyticsQueryDefinition::find($id);

        if ($query === null) {
            return false;
        }

        $query->delete();

        return true;
    }
}
