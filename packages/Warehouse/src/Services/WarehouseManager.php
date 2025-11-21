<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Services;

use Nexus\Warehouse\Contracts\WarehouseManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Warehouse management service
 */
final readonly class WarehouseManager implements WarehouseManagerInterface
{
    public function __construct(
        private WarehouseRepositoryInterface $repository,
        private string $tenantId,
        private LoggerInterface $logger
    ) {
    }
    
    public function createWarehouse(string $code, string $name, array $metadata = []): string
    {
        $this->logger->info('Creating warehouse', [
            'code' => $code,
            'name' => $name,
        ]);
        
        $warehouseId = $this->repository->create($this->tenantId, $code, $name, $metadata);
        
        $this->logger->info('Warehouse created', ['warehouse_id' => $warehouseId]);
        
        return $warehouseId;
    }
    
    public function getWarehouse(string $warehouseId): array
    {
        return $this->repository->findById($warehouseId);
    }
    
    public function listWarehouses(): array
    {
        return $this->repository->findByTenant($this->tenantId);
    }
}

/**
 * Warehouse repository contract
 */
interface WarehouseRepositoryInterface
{
    public function create(string $tenantId, string $code, string $name, array $metadata): string;
    public function findById(string $warehouseId): array;
    public function findByTenant(string $tenantId): array;
}
